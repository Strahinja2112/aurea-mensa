"use strict";

const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
const TABLE_SHAPES = ["round", "square", "rectangle", "oval", "corner"];

function normalizeRotation(value) {
	const rotation = Number(value || 0) % 360;

	return rotation < 0 ? rotation + 360 : rotation;
}

function parseTables(dataElement) {
	if (!dataElement) {
		return [];
	}

	try {
		const rows = JSON.parse(dataElement.textContent || "[]");

		return rows.map((row, index) => ({
			id: Number(row.id),
			name: String(row.name || `Sto ${index + 1}`),
			seats: Math.max(1, Number(row.seats || 1)),
			location_description: row.location_description || "",
			is_smoking: Number(row.is_smoking || 0),
			layout_x: Number(row.layout_x || defaultX(index)),
			layout_y: Number(row.layout_y || defaultY(index)),
			layout_rotation: normalizeRotation(row.layout_rotation),
			table_shape: TABLE_SHAPES.includes(row.table_shape)
				? row.table_shape
				: "round",
			selected: false,
			unavailable: false,
		}));
	} catch (error) {
		console.error(error);
		return [];
	}
}

function defaultX(index) {
	return [18, 42, 66, 82][index % 4];
}

function defaultY(index) {
	return clamp(24 + Math.floor(index / 4) * 24, 12, 88);
}

function linearSeats(count, start, end) {
	if (count <= 1) {
		return [(start + end) / 2];
	}

	return Array.from(
		{ length: count },
		(_, index) => start + ((end - start) * index) / (count - 1),
	);
}

function getEdgeChairPositions(seats, edges) {
	const positions = [];
	const base = Math.floor(seats / edges.length);
	let extra = seats % edges.length;

	edges.forEach((edge) => {
		const count = base + (extra > 0 ? 1 : 0);
		extra -= extra > 0 ? 1 : 0;

		if (count <= 0) {
			return;
		}

		const steps = linearSeats(count, 26, 74);

		steps.forEach((step) => {
			if (edge === "top") {
				positions.push({ x: step, y: 8, rotation: 180 });
			} else if (edge === "right") {
				positions.push({ x: 92, y: step, rotation: 270 });
			} else if (edge === "bottom") {
				positions.push({ x: step, y: 92, rotation: 0 });
			} else {
				positions.push({ x: 8, y: step, rotation: 90 });
			}
		});
	});

	return positions;
}

function getRectangleChairPositions(seats) {
	const positions = [];
	const sideCount = Math.max(0, seats - 8);
	const longSideSeats = seats - sideCount;
	const topCount = Math.ceil(longSideSeats / 2);
	const bottomCount = Math.floor(longSideSeats / 2);

	linearSeats(topCount, 24, 76).forEach((x) => {
		positions.push({ x, y: 9, rotation: 180 });
	});

	linearSeats(bottomCount, 24, 76).forEach((x) => {
		positions.push({ x, y: 91, rotation: 0 });
	});

	if (sideCount > 0) {
		const leftCount = Math.ceil(sideCount / 2);
		const rightCount = Math.floor(sideCount / 2);

		linearSeats(leftCount, 34, 66).forEach((y) => {
			positions.push({ x: 7, y, rotation: 90 });
		});

		linearSeats(rightCount, 34, 66).forEach((y) => {
			positions.push({ x: 93, y, rotation: 270 });
		});
	}

	return positions;
}

function distributeSeats(total, buckets) {
	const counts = Array.from({ length: buckets }, () => 0);

	for (let index = 0; index < total; index += 1) {
		counts[index % buckets] += 1;
	}

	return counts;
}

function getCornerChairPositions(seats) {
	const positions = [];
	const [innerBottomCount, innerRightCount, outerTopCount, outerLeftCount] =
		distributeSeats(seats, 4);

	linearSeats(innerBottomCount, 58, 88).forEach((x) => {
		positions.push({ x, y: 48, rotation: 0 });
	});

	linearSeats(innerRightCount, 58, 88).forEach((y) => {
		positions.push({ x: 48, y, rotation: 270 });
	});

	linearSeats(outerTopCount, 22, 78).forEach((x) => {
		positions.push({ x, y: 7, rotation: 180 });
	});

	linearSeats(outerLeftCount, 22, 78).forEach((y) => {
		positions.push({ x: 7, y, rotation: 90 });
	});

	return positions;
}

function getChairPositions(table) {
	const seats = Math.max(1, Number(table.seats || 1));

	if (table.table_shape === "rectangle" || table.table_shape === "oval") {
		if (table.table_shape === "oval") {
			return Array.from({ length: seats }, (_, index) => {
				const angle = (360 / seats) * index;
				const radians = ((angle - 90) * Math.PI) / 180;

				return {
					x: 50 + Math.cos(radians) * 45,
					y: 50 + Math.sin(radians) * 36,
					rotation: angle,
				};
			});
		}

		return getRectangleChairPositions(seats);
	}

	if (table.table_shape === "square") {
		return getEdgeChairPositions(seats, ["top", "right", "bottom", "left"]);
	}

	if (table.table_shape === "corner") {
		return getCornerChairPositions(seats);
	}

	return Array.from({ length: seats }, (_, index) => {
		const angle = (360 / seats) * index;
		const radians = ((angle - 90) * Math.PI) / 180;

		return {
			x: 50 + Math.cos(radians) * 43,
			y: 50 + Math.sin(radians) * 43,
			rotation: angle,
		};
	});
}

function renderTable(map, table, mode) {
	const button = document.createElement("button");
	button.type = "button";
	button.className = "layout-table";
	button.dataset.tableId = String(table.id);
	button.dataset.shape = table.table_shape;
	button.style.setProperty("--x", `${table.layout_x}%`);
	button.style.setProperty("--y", `${table.layout_y}%`);
	button.style.setProperty(
		"--rotation",
		`${normalizeRotation(table.layout_rotation)}deg`,
	);
	button.style.setProperty(
		"--counter-rotation",
		`${-normalizeRotation(table.layout_rotation)}deg`,
	);
	button.setAttribute("aria-label", `${table.name}, ${table.seats} mesta`);

	if (table.selected) {
		button.classList.add("selected");
	}

	if (table.is_smoking) {
		button.classList.add("smoking");
		button.setAttribute(
			"aria-label",
			`${table.name}, ${table.seats} mesta, pušačka zona`,
		);
	}

	if (table.unavailable) {
		button.classList.add("unavailable");
		button.setAttribute("aria-disabled", "true");
	}

	const figure = document.createElement("span");
	figure.className = "table-figure";

	const surface = document.createElement("span");
	surface.className = "table-surface";

	const label = document.createElement("span");
	label.className = "table-label";
	label.textContent = table.name;
	surface.appendChild(label);

	if (table.is_smoking) {
		const smokingIcon = document.createElement("span");
		smokingIcon.className = "table-smoking-icon";
		smokingIcon.innerHTML = '<i class="fa-solid fa-smoking" aria-hidden="true"></i>';
		surface.appendChild(smokingIcon);
	}

	figure.appendChild(surface);

	getChairPositions(table).forEach((position) => {
		const chair = document.createElement("span");
		chair.className = "layout-chair";
		chair.style.setProperty("--chair-x", `${position.x}%`);
		chair.style.setProperty("--chair-y", `${position.y}%`);
		chair.style.setProperty("--chair-rotation", `${position.rotation}deg`);
		figure.appendChild(chair);
	});

	button.appendChild(figure);

	const meta = document.createElement("span");
	meta.className = "table-meta-pill";
	meta.textContent =
		mode === "reserve" && table.unavailable
			? "Zauzeto"
			: `${table.seats} mesta`;
	button.appendChild(meta);

	return button;
}

function initAdmin(root) {
	const map = root.querySelector(".table-layout-map");
	const status = document.getElementById("tableLayoutStatus");
	const dataElement = document.getElementById(root.dataset.tablesSource);
	const saveUrl = root.dataset.saveUrl;
	const tables = parseTables(dataElement);
    const editDataElement = document.getElementById("reservationEditData");

    let editData = {
        editMode: false,
        reservationId: null,
        selectedTables: [],
        startTime: "",
        duration: 1
    };

    if (editDataElement) {
        try {
            editData = JSON.parse(editDataElement.textContent);
        } catch (e) {
            console.error(e);
        }
    }
	const deletedIds = [];
	let selectedId = null;
	let nextTemporaryId = -1;
	let drag = null;

	const controls = {
		add: document.getElementById("addLayoutTableButton"),
		fieldset: document.getElementById("layoutEditFields"),
		inspector: document.getElementById("layoutInspector"),
		hint: document.getElementById("layoutNoSelectionHint"),
		selectedName: document.getElementById("layoutSelectedName"),
		selectedMeta: document.getElementById("layoutSelectedMeta"),
		name: document.getElementById("layoutTableName"),
		location: document.getElementById("layoutTableLocation"),
		smoking: document.getElementById("layoutTableSmoking"),
		seats: document.getElementById("layoutSeatCount"),
		rotation: document.getElementById("layoutRotationValue"),
		shapeButtons: document.querySelectorAll("[data-layout-shape]"),
		decrease: document.getElementById("layoutDecreaseSeats"),
		increase: document.getElementById("layoutIncreaseSeats"),
		rotateLeft: document.getElementById("layoutRotateLeft"),
		rotateRight: document.getElementById("layoutRotateRight"),
		save: document.getElementById("saveLayoutButton"),
		remove: document.getElementById("removeLayoutTableButton"),
	};

	const getSelected = () =>
		tables.find((table) => table.id === selectedId) || null;

	function setStatus(message, type) {
		if (!status) {
			return;
		}

		status.innerHTML = message
			? `<div class="alert alert-${type} bg-${type} bg-opacity-20 border-0 text-white small rounded-3 mb-0">${message}</div>`
			: "";
	}

	function syncControls() {
		const table = getSelected();

		if (!controls.name) {
			return;
		}

		const hasSelection = Boolean(table);
		controls.inspector.classList.toggle("d-none", !hasSelection);
		controls.hint.classList.toggle("d-none", hasSelection);
		controls.remove.disabled = !hasSelection;

		if (!table) {
			controls.name.value = "";
			controls.location.value = "";
			controls.smoking.checked = false;
			controls.seats.textContent = "0";
			controls.rotation.textContent = "0°";
			controls.shapeButtons.forEach((button) =>
				button.classList.remove("active"),
			);
			return;
		}

		controls.name.value = table.name;
		controls.location.value = table.location_description || "";
		controls.smoking.checked = Boolean(table.is_smoking);
		controls.seats.textContent = String(table.seats);
		controls.rotation.textContent = `${normalizeRotation(table.layout_rotation)}°`;
		controls.shapeButtons.forEach((button) => {
			button.classList.toggle(
				"active",
				button.dataset.layoutShape === table.table_shape,
			);
		});
	}

	function render() {
		map.innerHTML = "";
		map.dataset.mode = "admin";

		if (tables.length === 0) {
			map.innerHTML =
				'<div class="layout-empty-state">Nema stolova. Dodajte prvi sto pomoću dugmeta iznad mape.</div>';
			return;
		}

		tables.forEach((table) => {
			table.selected = table.id === selectedId;
			const node = renderTable(map, table, "admin");

			node.addEventListener("pointerdown", (event) => {
				selectedId = table.id;
				syncControls();
				map
					.querySelectorAll(".layout-table.selected")
					.forEach((selectedNode) => {
						selectedNode.classList.remove("selected");
					});
				node.classList.add("selected");

				if (
					!window.matchMedia("(pointer: fine)").matches &&
					window.innerWidth < 768
				) {
					return;
				}

				const rect = map.getBoundingClientRect();
				drag = { id: table.id, rect };
				node.classList.add("dragging");
				node.setPointerCapture(event.pointerId);
				event.preventDefault();
			});

			node.addEventListener("pointermove", (event) => {
				if (!drag || drag.id !== table.id) {
					return;
				}

				table.layout_x = clamp(
					((event.clientX - drag.rect.left) / drag.rect.width) * 100,
					4,
					96,
				);
				table.layout_y = clamp(
					((event.clientY - drag.rect.top) / drag.rect.height) * 100,
					8,
					92,
				);
				node.style.setProperty("--x", `${table.layout_x}%`);
				node.style.setProperty("--y", `${table.layout_y}%`);
			});

			node.addEventListener("pointerup", () => {
				drag = null;
				node.classList.remove("dragging");
			});

			map.appendChild(node);
		});
	}

	function updateSelected(mutator) {
		const table = getSelected();

		if (!table) {
			return;
		}

		mutator(table);
		syncControls();
		render();
		setStatus("", "success");
	}

	if (!controls.name) {
		render();
		return;
	}

	controls.add.addEventListener("click", () => {
		const nextNumber = tables.length + deletedIds.length + 1;
		const table = {
			id: nextTemporaryId,
			name: `Sto ${nextNumber}`,
			seats: 4,
			location_description: "",
			is_smoking: 0,
			layout_x: 50,
			layout_y: 50,
			layout_rotation: 0,
			table_shape: "round",
			selected: true,
			unavailable: false,
		};

		nextTemporaryId -= 1;
		tables.push(table);
		selectedId = table.id;
		syncControls();
		render();
	});

	controls.name.addEventListener("input", () =>
		updateSelected((table) => {
			table.name = controls.name.value;
		}),
	);
	controls.location.addEventListener("input", () =>
		updateSelected((table) => {
			table.location_description = controls.location.value;
		}),
	);
	controls.smoking.addEventListener("change", () =>
		updateSelected((table) => {
			table.is_smoking = controls.smoking.checked ? 1 : 0;
		}),
	);
	controls.decrease.addEventListener("click", () =>
		updateSelected((table) => {
			table.seats = Math.max(1, table.seats - 1);
		}),
	);
	controls.increase.addEventListener("click", () =>
		updateSelected((table) => {
			table.seats = Math.min(20, table.seats + 1);
		}),
	);
	controls.shapeButtons.forEach((button) => {
		button.addEventListener("click", () =>
			updateSelected((table) => {
				if (TABLE_SHAPES.includes(button.dataset.layoutShape)) {
					table.table_shape = button.dataset.layoutShape;
				}
			}),
		);
	});
	controls.rotateLeft.addEventListener("click", () =>
		updateSelected((table) => {
			table.layout_rotation = normalizeRotation(table.layout_rotation - 15);
		}),
	);
	controls.rotateRight.addEventListener("click", () =>
		updateSelected((table) => {
			table.layout_rotation = normalizeRotation(table.layout_rotation + 15);
		}),
	);
	controls.remove.addEventListener("click", () => {
		const table = getSelected();

		if (!table || !confirm("Ukloniti izabrani sto iz rasporeda?")) {
			return;
		}

		if (table.id > 0) {
			deletedIds.push(table.id);
		}

		const index = tables.findIndex((item) => item.id === table.id);
		if (index !== -1) {
			tables.splice(index, 1);
		}

		selectedId = null;
		syncControls();
		render();
		setStatus(
			"Sto je uklonjen iz prikaza. Sačuvajte raspored da bi promena bila upisana.",
			"info",
		);
	});

	controls.save.addEventListener("click", async () => {
		setStatus("Čuvanje rasporeda...", "info");
		controls.save.disabled = true;

		try {
			const response = await fetch(saveUrl, {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({ tables, deleted_ids: deletedIds }),
			});
			const result = await response.json();

			setStatus(
				result.message || "Raspored je sačuvan.",
				result.success ? "success" : "danger",
			);

			if (result.success) {
				window.setTimeout(() => {
					window.location.reload();
				}, 500);
			}
		} catch (error) {
			console.error(error);
			setStatus("Greška pri komunikaciji sa serverom.", "danger");
		} finally {
			controls.save.disabled = false;
		}
	});

	syncControls();
	render();
}

function initReserve(root) {
	const map = root.querySelector(".table-layout-map");
	const dataElement = document.getElementById(root.dataset.tablesSource);
	const form = document.getElementById(root.dataset.formId);
	const selectedInputs = document.getElementById(root.dataset.selectedInputs);
	const timeframesUrl = root.dataset.timeframesUrl;
	const workingHoursElement = document.getElementById(
		root.dataset.workingHoursSource,
	);
	const timeline = document.getElementById("reservationTimeline");
	const timelinePanel = document.querySelector(".reservation-timeline-panel");
	const timeframeList = document.getElementById("reservationTimeframes");
	const timelineSummary = document.getElementById("reservationTimelineSummary");
	const startInput = document.getElementById("reservationStartTime");
	const durationInput = document.getElementById("reservationDurationHours");
	const tableInfo = {
		empty: document.getElementById("reserveTableInfoEmpty"),
		panel: document.getElementById("reserveTableInfo"),
		name: document.getElementById("reserveInfoName"),
		status: document.getElementById("reserveInfoStatus"),
		seats: document.getElementById("reserveInfoSeats"),
		location: document.getElementById("reserveInfoLocation"),
		smoking: document.getElementById("reserveInfoSmoking"),
		shape: document.getElementById("reserveInfoShape"),
	};
	const workingHours = parseWorkingHours(workingHoursElement);
	const tables = parseTables(dataElement);
	let busyIntervals = [];
	let busyByTable = {};
	let selection = null;
	let focusedTableId = null;

	function selectedIds() {
		return tables.filter((table) => table.selected).map((table) => table.id);
	}

	function selectedTables() {
		return tables.filter((table) => table.selected);
	}

	function focusedTable() {
		const selected = selectedTables();

		if (focusedTableId !== null) {
			const focused = selected.find((table) => table.id === focusedTableId);

			if (focused) {
				return focused;
			}
		}

		return selected[selected.length - 1] || null;
	}

	function tableShapeLabel(shape) {
		return (
			{
				round: "Okrugli",
				square: "Kvadratni",
				rectangle: "Pravougaoni",
				oval: "Ovalni",
				corner: "Ugaoni",
			}[shape] || "Standardni"
		);
	}

    function updateTableInfo() {
        const table = focusedTable();

        if (!tableInfo.empty || !tableInfo.panel) {
            return;
        }

        if (!table) {
            tableInfo.empty.classList.remove("d-none");
            tableInfo.panel.classList.add("d-none");
            return;
        }

        tableInfo.empty.classList.add("d-none");
        tableInfo.panel.classList.remove("d-none");

        tableInfo.name.textContent = table.name;
        tableInfo.status.textContent = table.unavailable ? "Zauzet" : "Dostupan";
        tableInfo.status.dataset.state = table.unavailable ? "busy" : "available";
        tableInfo.seats.textContent = `${table.seats} mesta`;
        tableInfo.location.textContent =
            table.location_description || "Nije navedeno";
        tableInfo.smoking.textContent = table.is_smoking
            ? "Pušačka zona"
            : "Nepušačka zona";
        tableInfo.shape.textContent = tableShapeLabel(table.table_shape);

        // sakrij dugme ako nije edit
        const submitWrap = document.querySelector(".reserve-submit-wrap");

        if (submitWrap) {
            submitWrap.style.display = editData.editMode ? "block" : "none";
        }
    }

	function updateInputs() {
		selectedInputs.innerHTML = "";

		selectedIds().forEach((id) => {
			const input = document.createElement("input");
			input.type = "hidden";
			input.name = "table_ids[]";
			input.value = String(id);
			selectedInputs.appendChild(input);
		});
	}

	function render() {
		map.innerHTML = "";
		map.dataset.mode = "reserve";

		if (tables.length === 0) {
			map.innerHTML =
				'<div class="layout-empty-state">Trenutno nema dostupnih stolova.</div>';
			return;
		}

		tables.forEach((table) => {
			const node = renderTable(map, table, "reserve");
			node.addEventListener("click", () => {
				if (table.unavailable) {
					return;
				}

				const isSelecting = !table.selected;
				table.selected = !table.selected;
				focusedTableId = isSelecting ? table.id : focusedTable()?.id || null;
				updateInputs();
				updateTableInfo();
				render();
				refreshTimeframes();
			});
			map.appendChild(node);
		});
	}

	function parseWorkingHours(element) {
		try {
			return JSON.parse(element?.textContent || "{}");
		} catch (error) {
			console.error(error);
			return {};
		}
	}

	function parseTimeToMinutes(value) {
		const [hours, minutes] = String(value).split(":").map(Number);
		return hours * 60 + (minutes || 0);
	}

	function formatMinutes(minutes) {
		const normalized = minutes >= 1440 ? minutes - 1440 : minutes;
		const hours = Math.floor(normalized / 60);
		const mins = normalized % 60;

		return `${String(hours).padStart(2, "0")}:${String(mins).padStart(2, "0")}`;
	}

	function getWorkingWindow(dateValue) {
		const date = new Date(`${dateValue}T12:00:00`);
		const isWeekend = date.getDay() === 0 || date.getDay() === 6;
		const hoursText = isWeekend ? workingHours.weekend : workingHours.weekdays;
		const [startText, endText] = String(hoursText || "08:00 - 23:00")
			.split("-")
			.map((part) => part.trim());
		const start = parseTimeToMinutes(startText);
		let end = parseTimeToMinutes(endText);

		if (end <= start) {
			end += 1440;
		}

		return { start, end };
	}

	function rangesOverlap(startA, endA, startB, endB) {
		return startA < endB && endA > startB;
	}

	function rangeIsAvailable(start, end) {
		if (
			!selectionWindow ||
			start < selectionWindow.start ||
			end > selectionWindow.end
		) {
			return false;
		}

		if (end - start < 60 || end - start > 360) {
			return false;
		}

		return !busyIntervals.some((interval) =>
			rangesOverlap(start, end, interval.start, interval.end),
		);
	}

	function tableRangeIsBusy(tableId, start, end) {
		return (busyByTable[String(tableId)] || []).some((interval) =>
			rangesOverlap(start, end, interval.start, interval.end),
		);
	}

	function findFirstAvailableRange() {
		if (!selectionWindow) {
			return null;
		}

		for (
			let start = selectionWindow.start;
			start + 60 <= selectionWindow.end;
			start += 60
		) {
			if (rangeIsAvailable(start, start + 60)) {
				return { start, end: start + 60 };
			}
		}

		return null;
	}

	let selectionWindow = null;

	function syncTimeInputs() {
		if (!selection) {
			startInput.value = "";
			durationInput.value = "";
			return;
		}

		startInput.value = formatMinutes(selection.start);
		durationInput.value = String((selection.end - selection.start) / 60);
	}

	function availableSegments() {
		if (!selectionWindow) {
			return [];
		}

		const sortedBusy = busyIntervals
			.map((interval) => ({
				start: Math.max(interval.start, selectionWindow.start),
				end: Math.min(interval.end, selectionWindow.end),
			}))
			.filter((interval) => interval.start < interval.end)
			.sort((a, b) => a.start - b.start);
		const segments = [];
		let cursor = selectionWindow.start;

		sortedBusy.forEach((interval) => {
			if (cursor < interval.start) {
				segments.push({ start: cursor, end: interval.start });
			}
			cursor = Math.max(cursor, interval.end);
		});

		if (cursor < selectionWindow.end) {
			segments.push({ start: cursor, end: selectionWindow.end });
		}

		return segments.filter((segment) => segment.end - segment.start >= 60);
	}

	function renderTimeframesList() {
		const segments = availableSegments();

		if (!timeframeList) {
			return;
		}

		if (segments.length === 0) {
			timeframeList.innerHTML =
				'<span class="timeframe-empty">Nema slobodnih termina za izabrane stolove.</span>';
			return;
		}
	}

	function renderTimeline() {
		if (!timeline || !timelineSummary) {
			return;
		}

		const activeTables = selectedTables();

		if (!selectionWindow || activeTables.length === 0) {
			timeline.innerHTML =
				'<div class="timeline-empty">Izaberite datum i sto.</div>';
			timeframeList.innerHTML = "";
			timelineSummary.textContent =
				"Izaberite datum i sto da biste videli termine.";
			syncTimeInputs();
			return;
		}

		const totalHours = (selectionWindow.end - selectionWindow.start) / 60;
		timeline.innerHTML = "";
		timeline.style.setProperty("--timeline-hours", String(totalHours));

		const board = document.createElement("div");
		board.className = "timeline-board";
		board.style.setProperty(
			"--timeline-track-width",
			`${Math.max(640, totalHours * 58)}px`,
		);

		const corner = document.createElement("div");
		corner.className = "timeline-corner";
		corner.textContent = "Sto";
		board.appendChild(corner);

		const hoursRow = document.createElement("div");
		hoursRow.className = "timeline-hours-row";
		hoursRow.style.setProperty("--timeline-hours", String(totalHours));

		for (let index = 0; index < totalHours; index += 1) {
			const slotStart = selectionWindow.start + index * 60;
			const hour = document.createElement("span");
			hour.className = "timeline-hour";
			hour.textContent = formatMinutes(slotStart);
			hoursRow.appendChild(hour);
		}

		board.appendChild(hoursRow);

		const labels = document.createElement("div");
		labels.className = "timeline-labels";
		const lanes = document.createElement("div");
		lanes.className = "timeline-lanes";
		lanes.style.setProperty("--timeline-hours", String(totalHours));

		activeTables.forEach((table) => {
			const label = document.createElement("div");
			label.className = "timeline-table-label";
			label.textContent = table.name;
			labels.appendChild(label);

			const lane = document.createElement("div");
			lane.className = "timeline-lane";
			lane.style.setProperty("--timeline-hours", String(totalHours));

			for (let index = 0; index < totalHours; index += 1) {
				const slotStart = selectionWindow.start + index * 60;
				const slotEnd = slotStart + 60;
				const slot = document.createElement("button");
				slot.type = "button";
				slot.className = "timeline-slot";
				slot.setAttribute(
					"aria-label",
					`${table.name}: ${formatMinutes(slotStart)} - ${formatMinutes(slotEnd)}`,
				);

				if (tableRangeIsBusy(table.id, slotStart, slotEnd)) {
					slot.classList.add("busy");
					slot.disabled = true;
				} else {
					slot.addEventListener("click", () =>
						setSelection(slotStart, slotEnd),
					);
				}

				lane.appendChild(slot);
			}

			lanes.appendChild(lane);
		});

		if (selection) {
			const range = document.createElement("div");
			range.className = "timeline-selection";
			range.style.left = `${((selection.start - selectionWindow.start) / (selectionWindow.end - selectionWindow.start)) * 100}%`;
			range.style.width = `${((selection.end - selection.start) / (selectionWindow.end - selectionWindow.start)) * 100}%`;
			range.innerHTML = `
				<button type="button" class="timeline-handle left" aria-label="Podesi početak"></button>
				<span>${formatMinutes(selection.start)} - ${formatMinutes(selection.end)}</span>
				<button type="button" class="timeline-handle right" aria-label="Podesi kraj"></button>
			`;
			lanes.appendChild(range);

			range
				.querySelector(".left")
				.addEventListener("pointerdown", (event) => startResize("left", event));
			range
				.querySelector(".right")
				.addEventListener("pointerdown", (event) =>
					startResize("right", event),
				);
			timelineSummary.textContent = `Izabrano: ${formatMinutes(selection.start)} - ${formatMinutes(selection.end)} (${(selection.end - selection.start) / 60}h)`;
		} else {
			timelineSummary.textContent = "Nema slobodnog termina od minimum 1 sat.";
		}

		board.appendChild(labels);
		board.appendChild(lanes);
		timeline.appendChild(board);
		renderTimeframesList();
		syncTimeInputs();
	}

	function setSelection(start, end) {
		if (!rangeIsAvailable(start, end)) {
			return;
		}

		selection = { start, end };
		renderTimeline();
	}

	function startResize(edge, event) {
		event.preventDefault();

		const lane = timeline.querySelector(".timeline-lane");
		const laneRect = lane?.getBoundingClientRect();
		const totalHours = (selectionWindow.end - selectionWindow.start) / 60;
		const hourWidth =
			laneRect && totalHours > 0 ? laneRect.width / totalHours : 58;

		if (!laneRect || !selection || hourWidth <= 0) {
			return;
		}

		const onMove = (moveEvent) => {
			const contentX = clamp(
				moveEvent.clientX - laneRect.left,
				0,
				laneRect.width,
			);
			const snappedHours = Math.round(contentX / hourWidth);
			const snapped = selectionWindow.start + snappedHours * 60;

			if (edge === "left") {
				const nextStart = clamp(
					snapped,
					Math.max(selectionWindow.start, selection.end - 360),
					selection.end - 60,
				);
				if (rangeIsAvailable(nextStart, selection.end)) {
					selection.start = nextStart;
					renderTimeline();
				}
			} else {
				const nextEnd = clamp(
					snapped,
					selection.start + 60,
					Math.min(selectionWindow.end, selection.start + 360),
				);
				if (rangeIsAvailable(selection.start, nextEnd)) {
					selection.end = nextEnd;
					renderTimeline();
				}
			}
		};

		const onUp = () => {
			document.removeEventListener("pointermove", onMove);
			document.removeEventListener("pointerup", onUp);
			document.removeEventListener("pointercancel", onUp);
		};

		document.addEventListener("pointermove", onMove);
		document.addEventListener("pointerup", onUp);
		document.addEventListener("pointercancel", onUp);
	}

	async function refreshTimeframes() {
		const formData = new FormData(form);
		const reservationDate = formData.get("reservation_date");
		const ids = selectedIds();

		if (!reservationDate || ids.length === 0) {
			selectionWindow = null;
			busyIntervals = [];
			busyByTable = {};
			selection = null;
			renderTimeline();
			return;
		}

		selectionWindow = getWorkingWindow(reservationDate);

		try {
			const response = await fetch(timeframesUrl, {
				method: "POST",
				headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    reservation_date: reservationDate,
                    table_ids: ids,
                    reservation_id: editData.reservationId,
                    start_time: startInput.value,
                    duration: durationInput.value
				}),
			});
			const result = await response.json();
			busyIntervals = (result.busy_intervals || []).map((interval) => ({
				start: parseTimeToMinutes(interval.start),
				end: parseTimeToMinutes(interval.end) || 1440,
			}));
			busyByTable = {};

			Object.entries(result.busy_by_table || {}).forEach(
				([tableId, intervals]) => {
					busyByTable[tableId] = intervals.map((interval) => ({
						start: parseTimeToMinutes(interval.start),
						end: parseTimeToMinutes(interval.end) || 1440,
					}));
				},
			);

			if (!selection || !rangeIsAvailable(selection.start, selection.end)) {
				selection = findFirstAvailableRange();
			}

			renderTimeline();
		} catch (error) {
			console.error(error);
			selection = null;
			renderTimeline();
		}
	}

	form.querySelectorAll('[name="reservation_date"]').forEach((input) => {
		input.addEventListener("change", refreshTimeframes);
	});

	window.AureaTableLayouts = window.AureaTableLayouts || {};
	window.AureaTableLayouts.reserve = {
		clearSelection() {
			tables.forEach((table) => {
				table.selected = false;
			});
			focusedTableId = null;
			updateInputs();
			updateTableInfo();
			render();
			selection = null;
			renderTimeline();
		},
		refreshAvailability: refreshTimeframes,
		refreshTimeframes,
	};
    const editDataElement = document.getElementById("reservationEditData");

    const editData = editDataElement
        ? JSON.parse(editDataElement.textContent)
        : {
            editMode: false,
            reservationId: null,
            selectedTables: [],
            startTime: "",
            duration: 1
        };

    if (editData.editMode) {

        tables.forEach(table => {
            if (editData.selectedTables.includes(table.id)) {
                table.selected = true;
            }
        });

        focusedTableId = editData.selectedTables[0] ?? null;

        if (editData.startTime) {
            startInput.value = editData.startTime;
        }

        durationInput.value = editData.duration;
    }

    updateInputs();
    render();
    updateTableInfo();
    refreshTimeframes();
}

document.addEventListener("DOMContentLoaded", () => {
    const durationSelect=document.getElementById("durationSelect");

    if(durationSelect){

        durationSelect.addEventListener("change",()=>{

            document.getElementById("reservationDurationHours").value=
                durationSelect.value;

            if(window.AureaTableLayouts?.reserve){

                window.AureaTableLayouts.reserve.refreshTimeframes();

            }

        });

    }

    const people=document.getElementById("peopleCount");

    if(people){

        people.addEventListener("input",()=>{

            document.getElementById("numberOfPeople").value=
                people.value;

        });

    }

    const smoking=document.getElementById("smokingPreference");

    if(smoking){

        smoking.addEventListener("change",()=>{

            document.getElementById("isSmoking").value=
                smoking.value;

        });

    }
	document.querySelectorAll("[data-table-layout]").forEach((root) => {
		if (root.dataset.mode === "admin") {
			initAdmin(root);
		}

		if (root.dataset.mode === "reserve") {
			initReserve(root);
		}
	});
});
