function initializeCalendar(){

    const calendarEl = document.getElementById('calendar');
    if(!calendarEl) return; // nothing to do on pages without a calendar

    // destroy previous instance if present
    if(window.fullCalendar && typeof window.fullCalendar.destroy === 'function'){
        try{ window.fullCalendar.destroy(); }catch(e){/*ignore*/}
        window.fullCalendar = null;
    }

    // Remove old listeners by replacing nodes where appropriate
    document.querySelectorAll('.btn.slot-btn').forEach(btn=>{
        const clone = btn.cloneNode(true);
        btn.parentNode.replaceChild(clone, btn);
    });
    const phoneNode = document.getElementById('phone');
    if(phoneNode && phoneNode.parentNode){
        const clonePhone = phoneNode.cloneNode(true);
        phoneNode.parentNode.replaceChild(clonePhone, phoneNode);
    }
    const saveBtnNode = document.getElementById('saveEvent');
    if(saveBtnNode && saveBtnNode.parentNode){
        const cloneSave = saveBtnNode.cloneNode(true);
        saveBtnNode.parentNode.replaceChild(cloneSave, saveBtnNode);
    }

    // state for this instance
    let selectedDate = null;
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

            // Reset all buttons first
            document.querySelectorAll('.slot-btn').forEach(b => {
                b.classList.remove('selected');
                b.disabled = false;
                b.style.opacity = '1';
                b.style.cursor = 'pointer';
            });

            // Disable already booked slots for this date
            const bookedSlots = calendar.getEvents().filter(event => {
                const eventDate = event.start.toISOString().split('T')[0];
                return eventDate === selectedDate;
            });

            bookedSlots.forEach(event => {
                // Extract time in HH:MM format
                const hours = String(event.start.getHours()).padStart(2, '0');
                const minutes = String(event.start.getMinutes()).padStart(2, '0');
                const eventStartTime = `${hours}:${minutes}`;

                document.querySelectorAll('.slot-btn').forEach(btn => {
                    if (btn.dataset.start === eventStartTime) {
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                        btn.style.cursor = 'not-allowed';
                    }
                });
            });


            // Show modal
            const myModal = new bootstrap.Modal(
                document.getElementById('myModal')
            );
            myModal.show();


        }
    });

    calendar.render();
     window.fullCalendar = calendar;

    // Phone input validation
    const phoneInput = document.getElementById('phone');
    if(phoneInput){
        phoneInput.addEventListener('input', function () {
            if (/[^0-9]/.test(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }

    // ===============================
    // Time Slot Selection (MULTI)
    // ===============================
    document.querySelectorAll('.btn.slot-btn').forEach(btn => {
        btn.addEventListener('click', () => {

            const start = btn.dataset.start;
            const end = btn.dataset.end;

            // Check if this slot is already selected in current selection
            const alreadySelected = selectedSlots.some(slot => slot.start === start);

            if (alreadySelected) {
                // Deselect it
                btn.classList.remove('selected');
                selectedSlots = selectedSlots.filter(
                    slot => slot.start !== start
                );
            } else {
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

                // Select it
                btn.classList.add('selected');
                selectedSlots.push({ start, end });
            }
        });
    });

    // ===============================
    // Save Event Button
    // ===============================
    const saveBtn = document.getElementById('saveEvent');
    if(saveBtn){
        saveBtn.addEventListener('click', function () {

        
        const phoneEl = document.getElementById('phone');
        const phone = phoneEl ? phoneEl.value.trim() : '';
        const phoneIsValid = /^[0-9]*$/.test(phone);

        if (selectedSlots.length === 0) {
            alert("at least one time slot are required");
            return;
        }

        if (!phoneIsValid) {
            if(phoneEl) phoneEl.classList.add('is-invalid');
            return;
        }

        // Add each selected slot as an event
        selectedSlots.forEach(slot => {
            calendar.addEvent({
                title: phone,
                start: `${selectedDate}T${slot.start}`,
                end: `${selectedDate}T${slot.end}`,
                allDay: false
            });
        });

        // Clear inputs
       
if(phoneEl){ phoneEl.value = ''; phoneEl.classList.remove('is-invalid'); }

        selectedSlots = [];

        document.querySelectorAll('.slot-btn')
            .forEach(b => b.classList.remove('selected'));

        // Hide modal
        const modalEl = document.getElementById('myModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if(modalInstance) modalInstance.hide();
        });
    } 
}

// call on initial load and expose for dynamic page loads
document.addEventListener('DOMContentLoaded', initializeCalendar);
window.initializeCalendar = initializeCalendar;