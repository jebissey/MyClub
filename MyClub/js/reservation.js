function calculateDuration(start, end) {
    if (!start || !end) return '';
    
    const diff = Math.abs(end - start);
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    let duration = [];
    if (days > 0) duration.push(`${days} jour${days > 1 ? 's' : ''}`);
    if (hours > 0) duration.push(`${hours} heure${hours > 1 ? 's' : ''}`);
    if (minutes > 0) duration.push(`${minutes} minute${minutes > 1 ? 's' : ''}`);
    
    return duration.length > 0 ? `Durée : ${duration.join(' et ')}` : '';
}

const commonConfig = {
    enableTime: true,
    time_24hr: true,
    dateFormat: "Y-m-d H:i",
    minuteIncrement: 15,
    locale: 'fr',
    minDate: "today"
};

const startPicker = flatpickr("#start_datetime", {
    ...commonConfig,
    onChange: function(selectedDates, dateStr) {
        endPicker.set('minDate', dateStr);
        updateDuration();
    }
});

const endPicker = flatpickr("#end_datetime", {
    ...commonConfig,
    onChange: function(selectedDates, dateStr) {
        updateDuration();
    }
});

function updateDuration() {
    const startDate = startPicker.selectedDates[0];
    const endDate = endPicker.selectedDates[0];
    
    if (startDate && endDate) {
        const duration = calculateDuration(startDate, endDate);
        document.getElementById('duration_display').innerHTML = duration;
    }
}

