document.addEventListener('DOMContentLoaded', function () {

    const calendarEl = document.getElementById('calendar');
    let selectedDate = null;

    // Store selected slots
    let selectedSlots = [];

    // ===============================
    // Initialize FullCalendar
    // ===============================
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: true,

        dateClick: function (info) {
            selectedDate = info.dateStr;

            // Reset slots
            selectedSlots = [];
            document.querySelectorAll('.slot-btn')
                .forEach(b => b.classList.remove('selected'));

            // Show modal
            const myModal = new bootstrap.Modal(
                document.getElementById('myModal')
            );
            myModal.show();
        }
    });

    calendar.render();

    // ===============================
    // Phone input validation
    // ===============================
    const phoneInput = document.getElementById('phone');

    phoneInput.addEventListener('input', function () {
        if (/[^0-9]/.test(this.value)) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

   // ===============================
    // Time Slot Selection (MULTI)
    // ===============================
    document.querySelectorAll('.slot-btn').forEach(btn => {
        btn.addEventListener('click', () => {

            const start = btn.dataset.start;
            const end = btn.dataset.end;

            // Check if this slot is already booked for the selected date
            const isBooked = calendar.getEvents().some(event => {
                const eventDate = event.start.toISOString().split('T')[0];
                const eventStart = event.start.toTimeString().slice(0, 5);
                return eventDate === selectedDate && eventStart === start;
            });

            if (isBooked) {
                alert('This time slot is already booked!');
                return;
            }

            btn.classList.toggle('selected');

            if (btn.classList.contains('selected')) {
                selectedSlots.push({ start, end });
            } else {
                selectedSlots = selectedSlots.filter(
                    slot => slot.start !== start
                );
            }
        });
    });

    // ===============================
    // Save Event Button
    // ===============================
    document.getElementById('saveEvent').addEventListener('click', function () {

        const username = document.getElementById('username').value.trim();
        const phone = phoneInput.value.trim();
        const phoneIsValid = /^[0-9]*$/.test(phone);

        if (!username || selectedSlots.length === 0) {
            alert("Username and at least one time slot are required");
            return; 
        }

        if (!phoneIsValid) {
            phoneInput.classList.add('is-invalid');
            return;
        }

        // Add each selected slot as an event
        selectedSlots.forEach(slot => {
            calendar.addEvent({
                title: username,
                start: `${selectedDate}T${slot.start}`,
                end: `${selectedDate}T${slot.end}`,
                allDay: false
            });
        });

        // Clear inputs
        document.getElementById('username').value = '';
        phoneInput.value = '';
        phoneInput.classList.remove('is-invalid');

        selectedSlots = [];

        document.querySelectorAll('.slot-btn')
            .forEach(b => b.classList.remove('selected'));

        // Hide modal
        const modalEl = document.getElementById('myModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        modalInstance.hide();
    });

});
