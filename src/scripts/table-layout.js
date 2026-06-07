(function () {
	"use strict";

	const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

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
				table_shape: ["round", "square"].includes(row.table_shape)
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

	function renderTable(map, table, mode) {
		const button = document.createElement("button");
		button.type = "button";
		button.className = "layout-table";
		button.dataset.tableId = String(table.id);
		button.dataset.shape = table.table_shape;
		button.style.setProperty("--x", `${table.layout_x}%`);
		button.style.setProperty("--y", `${table.layout_y}%`);
		button.setAttribute("aria-label", `${table.name}, ${table.seats} mesta`);

		if (table.selected) {
			button.classList.add("selected");
		}

		if (table.unavailable) {
			button.classList.add("unavailable");
			button.setAttribute("aria-disabled", "true");
		}

		const surface = document.createElement("span");
		surface.className = "table-surface";

		const label = document.createElement("span");
		label.className = "table-label";
		label.textContent = table.name;
		surface.appendChild(label);
		button.appendChild(surface);

		for (let index = 0; index < table.seats; index += 1) {
			const chair = document.createElement("span");
			chair.className = "layout-chair";
			chair.style.setProperty("--angle", `${(360 / table.seats) * index}deg`);
			button.appendChild(chair);
		}

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
		let selectedId = tables[0] ? tables[0].id : null;
		let drag = null;

		const controls = {
			name: document.getElementById("layoutTableName"),
			location: document.getElementById("layoutTableLocation"),
			smoking: document.getElementById("layoutTableSmoking"),
			seats: document.getElementById("layoutSeatCount"),
			round: document.getElementById("layoutShapeRound"),
			square: document.getElementById("layoutShapeSquare"),
			decrease: document.getElementById("layoutDecreaseSeats"),
			increase: document.getElementById("layoutIncreaseSeats"),
			save: document.getElementById("saveLayoutButton"),
			deleteId: document.getElementById("deleteTableId"),
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

			if (!table || !controls.name) {
				return;
			}

			controls.name.value = table.name;
			controls.location.value = table.location_description || "";
			controls.smoking.checked = Boolean(table.is_smoking);
			controls.seats.textContent = String(table.seats);
			controls.round.classList.toggle("active", table.table_shape === "round");
			controls.square.classList.toggle(
				"active",
				table.table_shape === "square",
			);
			controls.deleteId.value = String(table.id);
		}

		function render() {
			map.innerHTML = "";
			map.dataset.mode = "admin";

			if (tables.length === 0) {
				map.innerHTML =
					'<div class="layout-empty-state">Nema stolova. Dodajte prvi sto pomoću forme desno.</div>';
				return;
			}

			tables.forEach((table) => {
				table.selected = table.id === selectedId;
				const node = renderTable(map, table, "admin");

				node.addEventListener("pointerdown", (event) => {
					selectedId = table.id;
					syncControls();
					map.querySelectorAll(".layout-table.selected").forEach((selectedNode) => {
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
		controls.round.addEventListener("click", () =>
			updateSelected((table) => {
				table.table_shape = "round";
			}),
		);
		controls.square.addEventListener("click", () =>
			updateSelected((table) => {
				table.table_shape = "square";
			}),
		);

		controls.save.addEventListener("click", async () => {
			setStatus("Čuvanje rasporeda...", "info");
			controls.save.disabled = true;

			try {
				const response = await fetch(saveUrl, {
					method: "POST",
					headers: { "Content-Type": "application/json" },
					body: JSON.stringify({ tables }),
				});
				const result = await response.json();

				setStatus(
					result.message || "Raspored je sačuvan.",
					result.success ? "success" : "danger",
				);
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
		const availabilityUrl = root.dataset.availabilityUrl;
		const tables = parseTables(dataElement);

		function selectedIds() {
			return tables.filter((table) => table.selected).map((table) => table.id);
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

					table.selected = !table.selected;
					updateInputs();
					render();
				});
				map.appendChild(node);
			});
		}

		async function refreshAvailability() {
			const formData = new FormData(form);
			const reservationDate = formData.get("reservation_date");
			const startTime = formData.get("start_time");
			const durationHours = Number(formData.get("duration_hours"));

			if (!reservationDate || !startTime || !durationHours) {
				tables.forEach((table) => {
					table.unavailable = false;
				});
				render();
				return;
			}

			try {
				const response = await fetch(availabilityUrl, {
					method: "POST",
					headers: { "Content-Type": "application/json" },
					body: JSON.stringify({
						reservation_date: reservationDate,
						start_time: startTime,
						duration_hours: durationHours,
					}),
				});
				const result = await response.json();
				const unavailableIds = new Set(
					(result.unavailable_table_ids || []).map(Number),
				);

				tables.forEach((table) => {
					table.unavailable = unavailableIds.has(table.id);
					if (table.unavailable) {
						table.selected = false;
					}
				});

				updateInputs();
				render();
			} catch (error) {
				console.error(error);
			}
		}

		form
			.querySelectorAll(
				'[name="reservation_date"], [name="start_time"], [name="duration_hours"]',
			)
			.forEach((input) => {
				input.addEventListener("change", refreshAvailability);
			});

		window.AureaTableLayouts = window.AureaTableLayouts || {};
		window.AureaTableLayouts.reserve = {
			clearSelection() {
				tables.forEach((table) => {
					table.selected = false;
				});
				updateInputs();
				render();
			},
			refreshAvailability,
		};

		updateInputs();
		render();
	}

	document.addEventListener("DOMContentLoaded", () => {
		document.querySelectorAll("[data-table-layout]").forEach((root) => {
			if (root.dataset.mode === "admin") {
				initAdmin(root);
			}

			if (root.dataset.mode === "reserve") {
				initReserve(root);
			}
		});
	});
})();
