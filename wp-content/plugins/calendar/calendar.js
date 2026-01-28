//full calendar functions 
var griffth = '';



function getInitialView() {
    if (window.innerWidth >= 768 && window.innerWidth < 1200) {
        return 'timeGridWeek';
    } else if (window.innerWidth <= 768) {
        return 'listMonth';
    } else {
        return 'dayGridMonth';
    }
}

var str_dt = function formatDate(date) {
    var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    var d = new Date(date),
        month = '' + monthNames[(d.getMonth())],
        day = '' + d.getDate(),
        year = d.getFullYear();
    if (month.length < 2)
        month = '0' + month;
    if (day.length < 2)
        day = '0' + day;
    return [day + " " + month, year].join(',');
};

function tConvert(time) {
    var t = time.split(":");
    var hours = t[0];
    var minutes = t[1];
    var newformat = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    return (hours + ':' + minutes + ' ' + newformat);
}

function getTime(params) {
    params = new Date(params);
    if (params.getHours() != null) {
        var hour = params.getHours();
        var minute = (params.getMinutes()) ? params.getMinutes() : 0;
        return hour + ":" + minute;
    }
}




var date = new Date();
var d = date.getDate();
var m = date.getMonth();
var y = date.getFullYear();


var defaultEventsField = document.getElementById('defaultEventsField');
var defaultEvents = JSON.parse(defaultEventsField.value);
// console.log(defaultEvents);



var myButton = document.getElementById("my-button");
var waitlist = document.getElementById("my-button-waitlist");
myButton.addEventListener("click", addNewEvent);
waitlist.addEventListener("click", showWaitlistModal);
const modal = document.getElementById("exampleModal");
const waitlistModal = document.getElementById("waitlistModal");
var datePicker = document.getElementById('date-picker');
var bookingImg = document.getElementById('booking_img');
function addNewEvent(info) {
    var currentDate = new Date();
    var currentDateString = currentDate.toISOString().split('T')[0];
    flatpickr(datePicker, {
        mode: 'range',
        defaultDate: [info.dateStr],
        minDate: currentDateString, // Set the minimum date


    });
    var modalInstance = bootstrap.Modal.getInstance(modal);
    if (!modalInstance) {
        modalInstance = new bootstrap.Modal(modal);
    }
    modalInstance.show();
}

bookingImg.addEventListener('click', function () {
    datePicker._flatpickr.open();
});


function showWaitlistModal() {
    var modalInstance = bootstrap.Modal.getInstance(waitlistModal);
    if (!modalInstance) {
        modalInstance = new bootstrap.Modal(waitlistModal);
    }
    modalInstance.show();
}



const eventModal = document.getElementById("event-modal");

function eventModalInfo(info) {

    selectedEvent = info.event;
    document.getElementById("modal-title-event").innerHTML = "";
      var modalTitle = document.getElementById('modal-title-event');


    var st_date = selectedEvent.start;
    var ed_date = selectedEvent.end;
    var date_r = function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();
        if (month.length < 2)
            month = '0' + month;
        if (day.length < 2)
            day = '0' + day;
        return [year, month, day].join('-');
    };

    var updateDay = null;
    if (ed_date != null) {
        var endUpdateDay = new Date(ed_date);
        updateDay = endUpdateDay.setDate(endUpdateDay.getDate() - 1);
    }
    var r_date = ed_date == null ? (str_dt(st_date)) : (str_dt(st_date)) + ' to ' + (str_dt(updateDay));
    var er_date = ed_date == null ? (date_r(st_date)) : (date_r(st_date)) + ' to ' + (date_r(updateDay));


    document.getElementById("event-start-date-tag").innerHTML = r_date;


    modalTitle.innerText = selectedEvent.title;

    var modalInstance = bootstrap.Modal.getInstance(eventModal);
    if (!modalInstance) {
        modalInstance = new bootstrap.Modal(eventModal);
    }
    modalInstance.show();
}


var calendarEl = document.getElementById('calendar');

var calendar = new FullCalendar.Calendar(calendarEl, {
    timeZone: 'local',
    editable: false, // Set to false to make events non-movable
    
    droppable: true,
    selectable: true,
    navLinks: true,
    initialView: getInitialView(),
    themeSystem: 'bootstrap',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listMonth'

    },
    showNonCurrentDates: false, // Only show current month's dates
    validRange: {
      start: moment().startOf('month'), // Start from the beginning of the current month
      end: moment().endOf('month') // End at the end of the current month
    },
    windowResize: function (view) {
        var newView = getInitialView();
        calendar.changeView(newView);
    },

    eventClick: function (info) {
        eventModalInfo(info);
    },
    eventResize: function (info) {
        var indexOfSelectedEvent = defaultEvents.findIndex(function (x) {
            return x.id == info.event.id
        });
        if (defaultEvents[indexOfSelectedEvent]) {
            defaultEvents[indexOfSelectedEvent].title = info.event.title;
            defaultEvents[indexOfSelectedEvent].start = info.event.start;
            defaultEvents[indexOfSelectedEvent].end = (info.event.end) ? info.event.end : null;
            defaultEvents[indexOfSelectedEvent].allDay = info.event.allDay;
            defaultEvents[indexOfSelectedEvent].className = info.event.classNames[0];
            defaultEvents[indexOfSelectedEvent].description = (info.event._def.extendedProps.description) ? info.event._def.extendedProps.description : '';
            defaultEvents[indexOfSelectedEvent].location = (info.event._def.extendedProps.location) ? info.event._def.extendedProps.location : '';
        }
        upcomingEvent(defaultEvents);
    },
    dateClick: function (info) {
        // Your existing dateClick logic
        if (!info.jsEvent.target.classList.contains('add-event-button')) {
            // Perform your existing logic only if the click is not on the button
        }
    },



    events: defaultEvents,
    dayCellDidMount: function (info) {
        var dateSquare = info.el;
    
        if (dateSquare) {
            var currentDate = new Date();
            currentDate.setHours(0, 0, 0, 0); 
    
            var cellDate = info.date;
            cellDate.setHours(0, 0, 0, 0); 
    
            var dayFrame = dateSquare.querySelector('.fc-daygrid-day-events');
            var dateElement = dateSquare.querySelector('.fc-daygrid-day-number');
    
            if (dateElement) {
                dateElement.classList.add('large-font'); 
                dateElement.style.fontSize = '23px'; 
                dateElement.style.pointerEvents = 'none'; // Prevent clicks

            }
    
            if (dayFrame) {
                var addButton = document.createElement('a');
                addButton.href = '#';
                addButton.innerHTML = '&nbsp;&nbsp;Book Now';
                addButton.className = 'add-event-button small text-blue font-size-12 z-index-999';
                addButton.style.zIndex = '999';

                var viewButton = document.createElement('a');
                viewButton.href = '#';
                viewButton.innerHTML = '&nbsp;&nbsp;Booked Members';
                viewButton.className = 'view-bookings-button small text-blue font-size-12 z-index-999';
                viewButton.style.zIndex = '999';

                var content = document.createElement('div');
                content.innerHTML = '&nbsp;&nbsp;<p style="font-size:12px">Available spaces: Loading...</p>';
    
                var dateStr = new Date(info.date.getTime() - info.date.getTimezoneOffset() * 60000).toISOString();
                var availDate = dateStr.split('T')[0];
                
                var dateStr = dateStr.split('T')[0] + 'T00:00:00.000Z';
    
                var hostUrl = window.location.origin;
                var apiUrl = hostUrl+griffth+'/wp-content/plugins/calendar/get_available_spaces.php';
    
                // fetch(apiUrl, {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //     },
                //     body: JSON.stringify({ date: dateStr }),
                // })
                // .then(response => {
                //     if (!response.ok) {
                //         throw new Error('Network response was not ok');
                //     }
                //     return response.json();
                // })
                // .then(data => {
                //     var remainingSpaces = data.remainingSpaces;
                //     content.innerHTML = '<p style="font-size:12px"> &nbsp;&nbsp;Available spaces: ' + remainingSpaces + '</p>';
                // })
                // .catch(error => {
                //     console.error('There was a problem with the fetch operation:', error);
                // });
                
                content.innerHTML = '<p style="font-size:12px" id="availDate_'+availDate+'">Available Spaces:</p>';
                var dateStr = new Date(info.date.getTime() - info.date.getTimezoneOffset() * 60000).toISOString();
                addButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
    
                    var fakeInfo = {
                        date: info.date,
                        dateStr: dateStr,
                        dayEl: info.el
                    };
    
                    addNewEvent(fakeInfo);
                });
    

                viewButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
    
                    var fakeInfo = {
                        date: info.date,
                        dateStr: dateStr,
                        dayEl: info.el
                    };
    
                    viewBookings(fakeInfo);

                });
                
    
    
                // Check if the date is in the future, then add the "Book Now" button
                if (cellDate >= currentDate) {
                    info.el.appendChild(addButton);
                    info.el.appendChild(content);
                }
                info.el.appendChild(viewButton);

            }
        }
    },
    
    
    
    
    eventReceive: function (info) {
        var newid = parseInt(info.event.id);
        var newEvent = {
            id: newid,
            title: info.event.title,
            start: info.event.start,
            allDay: info.event.allDay,
            className: info.event.classNames[0]
        };
        defaultEvents.push(newEvent);
        upcomingEvent(defaultEvents);
    }
    ,
});

calendar.render();

upcomingEvent(defaultEvents);


function viewBookings(info) {
    var dateStr = info.dateStr;
    
    var hostUrl = window.location.origin;
    var apiUrl = hostUrl +griffth+ '/wp-content/plugins/calendar/get_booking_data.php';

    var hiddenNameValue = document.getElementById('hidden_name').value;

    // Disable all elements with class name 'view-bookings-button'
    var viewButtons = document.getElementsByClassName('view-bookings-button');
    console.log(viewButtons);
    for (var i = 0; i < viewButtons.length; i++) {
        viewButtons[i].style.pointerEvents = 'none';
    }

    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ date: dateStr, hiddenName: hiddenNameValue }), 
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        displayModal(data);
        
        for (var i = 0; i < viewButtons.length; i++) {
            viewButtons[i].style.pointerEvents = 'auto';
        }
    })
    .catch(error => {
        console.error('There was a problem with the fetch operation:', error);
    });
}



function viewAvailableSpaces(info, dateStr) {
    var hostUrl = window.location.origin;
    var apiUrl = hostUrl +griffth+ '/wp-content/plugins/calendar/get_available_spaces.php';


    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ date: dateStr }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        
    })
    .catch(error => {
        // Handle errors during the fetch
        console.error('There was a problem with the fetch operation:', error);
    });
}


function displayModal(data) {
    var modalBody = document.getElementById('table-content');
    modalBody.innerHTML = '';

    if (data.length === 0) {
        // Show message if no data
        var messageCard = document.createElement('div');
        messageCard.classList.add('card', 'mb-3', 'text-center', 'p-3');

        var messageBody = document.createElement('div');
        messageBody.classList.add('card-body');

        var messageText = document.createElement('p');
        messageText.classList.add('mb-0', 'fw-bold');
        messageText.textContent = 'No bookings are available at the moment. If you proceed with your booking, please note that it will be subject to approval before confirmation.';

        messageBody.appendChild(messageText);
        messageCard.appendChild(messageBody);
        modalBody.appendChild(messageCard);
    } else {

    data.forEach(booking => {
        var bookingDetails = JSON.parse(booking.booking_details);

        // Create a card for each booking
        var cardElement = document.createElement('div');
        cardElement.classList.add('card', 'mb-3');

        // Card body
        var cardBody = document.createElement('div');
        cardBody.classList.add('card-body');

        // Title: User - [username] centered at the top
        // var cardHeader = document.createElement('h5');
        // cardHeader.classList.add('card-title', 'text-center');
        // cardHeader.textContent = `User - ${booking.user_nicename}`;
        // cardBody.appendChild(cardHeader);

        // Create a row for displaying the data
        var rowElement = document.createElement('div');
        rowElement.classList.add('row');

        // Left side: Table headings
        var colLeft = document.createElement('div');
        colLeft.classList.add('col-md-6');

        var tableLeft = document.createElement('table');
        tableLeft.classList.add('table', 'table-borderless');

        var tbodyLeft = document.createElement('tbody');
        
        // Add table headings
        tbodyLeft.innerHTML = `
            <tr><th><b>User</b></th></tr>
            <tr><th><b>Number of Guests</b></th></tr>
            <tr><th><b>Number of Hunters</b></th></tr>
            <tr><th><b>Preferred Hunt</b></th></tr>
            <tr><th><b>Dog Gender</b></th></tr>
            <tr><th><b>Breed of Dog</b></th></tr>
            <tr><th><b>Special Instructions</b></th></tr>
        `;

        tableLeft.appendChild(tbodyLeft);
        colLeft.appendChild(tableLeft);

        // Right side: Data
        var colRight = document.createElement('div');
        colRight.classList.add('col-md-6');

        var tableRight = document.createElement('table');
        tableRight.classList.add('table', 'table-borderless');

        var tbodyRight = document.createElement('tbody');
        
        // Add data values
        tbodyRight.innerHTML = `
            <tr><td>${booking.user_nicename}</td></tr>
            <tr><td>${bookingDetails.numberOfGuests}</td></tr>
            <tr><td>${bookingDetails.numberOfHunters}</td></tr>
            <tr><td>${bookingDetails.preferredHunt}</td></tr>
            <tr><td>${bookingDetails.dogGender}</td></tr>
            <tr><td>${bookingDetails.breed}</td></tr>
            <tr><td>${bookingDetails.specialInstructions}</td></tr>
        `;

        tableRight.appendChild(tbodyRight);
        colRight.appendChild(tableRight);

        // Append both columns (left and right) to the row
        rowElement.appendChild(colLeft);
        rowElement.appendChild(colRight);

        // Append the row to the card body
        cardBody.appendChild(rowElement);

        // Change card color if `is_Delete` is 1
        if (booking.is_Delete === 1) {
            cardElement.style.backgroundColor = 'red';
            cardBody.style.color = 'white';
        }

        // Append the card body to the card
        cardElement.appendChild(cardBody);

        // Append the card to the modal body
        modalBody.appendChild(cardElement);
    });
}

    var bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    bookingModal.show();
}


var alldatedata = '';

function fetchMyAvailSpace() {
    var date = calendar.getDate();
    var monthName = date.toLocaleString('en-US', { month: 'long' });
    var year = date.getFullYear(); 
    var hostUrl = window.location.origin;
    var apiUrl = hostUrl +griffth+ '/wp-content/plugins/calendar/get_avail_space.php';

    return new Promise((resolve, reject) => {
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ selected_month: monthName, selected_year: year }),
        })
        .then(response => response.json()) 
        .then(data => {
            // Iterate over the keys (dates) in the data object
            Object.keys(data.datesAndRemainingSpaces).forEach(date => {
                var id = 'availDate_' + date;
                var availableSpaces = data.datesAndRemainingSpaces[date];
                console.log(id);
                document.getElementById(id).innerHTML = 'Available Spaces: ' + availableSpaces;
            });
        
            // Resolve the promise with the fetched data
            resolve(data);
        })
        
        .catch(error => {
            console.error('Error fetching my_avail_space:', error);
            reject(error);
        });
    });
}

fetchMyAvailSpace().then(() => {
    console.log('outsideFunction', alldatedata);
});






function fetchMyCalendarTitle() {
    var date = calendar.getDate();
    var monthName = date.toLocaleString('en-US', { month: 'long' });
    var year = date.getFullYear(); 
    var hostUrl = window.location.origin;
                var apiUrl = hostUrl +griffth+'/wp-content/plugins/calendar/get_my_calendar_title.php';

    fetch(apiUrl, {
                    method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ selected_month: monthName,
            selected_year: year, }),
    })
    .then(response => response.text()) 
    .then(data => {
        updateDescriptionBox(data);
    })
    .catch(error => {
        console.error('Error fetching my_calendar_title:', error);
    });
}


fetchMyCalendarTitle();

document.querySelectorAll('.fc-button').forEach(function (button) {
    button.addEventListener('click', function () {
        fetchMyCalendarTitle();
        fetchMyAvailSpace();

    });
});

function updateDescriptionBox(title) {
    var descriptionBox = document.querySelector('.alert.alert-danger');
    if (descriptionBox) {
        descriptionBox.innerHTML = '<strong>Description box: &nbsp; </strong>' + title;
    } else {
        console.error('Description box not found.');
    }
}

function upcomingEvent(a) {
    a.sort(function (o1, o2) {
        return (new Date(o1.start)) - (new Date(o2.start));
    });
    document.getElementById("upcoming-event-list").innerHTML = null;
    Array.from(a).forEach(function (element) {
        var title = element.title;
        var title_class = element.className;
        if (element.end) {
            endUpdatedDay = new Date(element.end);
            var updatedDay = endUpdatedDay.setDate(endUpdatedDay.getDate() - 1);
        }
        var e_dt = updatedDay ? updatedDay : undefined;
        if (e_dt == "Invalid Date" || e_dt == undefined) {
            e_dt = null;
        } else {
            const newDate = new Date(e_dt).toLocaleDateString('en', { year: 'numeric', month: 'numeric', day: 'numeric' });
            e_dt = new Date(newDate)
                .toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                })
                .split(" ")
                .join(" ");
        }
        var st_date = element.start ? str_dt(element.start) : null;
        var ed_date = updatedDay ? str_dt(updatedDay) : null;
        if (st_date === ed_date) {
            e_dt = null;
        }
        var startDate = element.start;
        if (startDate === "Invalid Date" || startDate === undefined) {
            startDate = null;
        } else {
            const newDate = new Date(startDate).toLocaleDateString('en', { year: 'numeric', month: 'numeric', day: 'numeric' });
            startDate = new Date(newDate)
                .toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                })
                .split(" ")
                .join(" ");
        }

        var end_dt = (e_dt) ? " to " + e_dt : '';
        var category = (element.className).split("-");
        var description = (element.description) ? element.description : "";
        var e_time_s = tConvert(getTime(element.start));
        var e_time_e = tConvert(getTime(updatedDay));
        if (e_time_s == e_time_e) {
            var e_time_s = title;
            var e_time_e = null;
        }
        var e_time_e = (e_time_e) ? " to " + e_time_e : "";
        console.log('end'+ end_dt);

        var className = title_class.replace('bg-', '').replace('-subtle', '');


        u_event = "<div class='card mb-3'>\
                <div class='card-body'>\
                    <div class='d-flex mb-3'>\
                        <div class='flex-grow-1'><i class='mdi mdi-checkbox-blank-circle me-2 text-" + category[1] + "'></i><span class='fw-medium'>" + startDate + end_dt + " </span></div>\
                        <div class='flex-shrink-0'><small class='badge " + title_class + " text-"+className+" ms-auto'>" + e_time_s + "</small></div>\
                    </div>\
                    <p class='text-muted text-truncate-two-lines mb-0'> " + description + "</p>\
                </div>\
            </div>";

        document.getElementById("upcoming-event-list").innerHTML += u_event;
    });
};


