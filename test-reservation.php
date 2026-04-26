<?php
require_once 'init.php';
?>

<button onclick="testReservation()">Testiraj rezervaciju</button>

<pre id="result"></pre>

<script>
    async function testReservation() {
        const response = await fetch('server/actions/create_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                table_ids: [1],
                reservation_date: '2026-05-01',
                start_time: '18:00',
                duration_hours: 2
            })
        });

        const result = await response.json();
        document.getElementById('result').textContent = JSON.stringify(result, null, 2);
    }
</script>