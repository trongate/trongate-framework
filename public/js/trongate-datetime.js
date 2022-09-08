// DATE/TIME PICKER SETTINGS START : PLEASE FEEL FREE TO CHANGE THE SETTINGS BELOW THIS LINE:
var weekStartsOn = 1; // 0 = Sunday, 1 = Monday

// the localeString spezifies the locale used to parse the date and to output the date
var localeString = 'en-GB'; //details:  https://www.w3schools.com/jsref/jsref_tolocalestring.asp

var dateFormatObj = {
    dateStyle: 'long'
}

//please change these settings to suit your language
var dayNames = [
    "Sunday",
    "Monday", 
    "Tuesday", 
    "Wednesday", 
    "Thursday", 
    "Friday", 
    "Saturday"
];

var monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
];

var unavailableBefore;
var unavailableAfter;



// DATE/TIME PICKER SETTINGS END : DO NOT EDIT BELOW THIS LINE!!!

var pathArray = window.location.pathname.split( '/' );
var segment3 = pathArray[3];
var datePickerFields = _('.date-picker');
var timePickerFields = _('.time-picker');
var dateTimePickerFields = _('.datetime-picker');
var dateRangePickerFields = _('.date-range');
var targetInputValue = ''; //the value of the form input that has been clicked
var targetInputValueSm  = ''; //alt version of targetInputValue (with time removed)
var boxDayNumLZ, boxDayNum, boxYearNum;

if ((datePickerFields.length>0) || (timePickerFields.length>0) || (dateTimePickerFields.length>0) || (dateRangePickerFields.length>0) ) {

    //initialize date/time picker vars
    var assumedDate = new Date;
    var todayDate = new Date;
    var activeEl;
    var activeType;
    var activePopUp;
    var datePickerCanvas = 'large';
    var datePickerTblTopRow = buildTopRow(); //top row never changes so do this once & early for better performance

    //timePicker settings
    var currentHour = assumedDate.getHours();
    currentHour = addZeroBefore(currentHour);
    var currentMinute = assumedDate.getMinutes();
    currentMinute = addZeroBefore(currentMinute);
    var clickedTimePickerEl;

    //check for date/time picker fields;
    //date-picker, time-picker, datetime-picker, date-range, time-range
    if (datePickerFields.length>0) {
        initDatePickers();
        disableDatePickerInputs('date-picker');
        listenForOffsideClicks('date-picker', 'datepicker-calendar');
    }
    
    if (timePickerFields.length>0) {
        initTimePickers();
        listenForOffsideClicks('time-picker', 'timepicker-popup');
    }

    if (dateTimePickerFields.length>0) {
        initDateTimePickers();
        listenForOffsideClicks('datetime-picker', 'datetime-picker-calendar');
    }

    if (dateRangePickerFields.length>0) {
        initDateRangePickers();
        listenForOffsideClicks('date-range', 'datepicker-calendar');
    }

}

/* 
 * retrive the date format string from the locale
 * the format pieces are YYYY MM DD and the delimeter
 */ 
function getDateFormatStringFromLocale (locale) {
    const formatObj = new Intl.DateTimeFormat(locale).formatToParts(new Date());

    return formatObj
        .map(obj => {
            switch (obj.type) {
                case "day":
                    return "DD";
                case "month":
                    return "MM";
                case "year":
                    return "YYYY";
                default:
                    return obj.value;
            }
        })
        .join("");
}

/*
 * parse a Date obj from input using the locale
 * given in the global variable localeString.
 * returns a date obj or 'Invalid Date' like date.parse
 */
function parseDate (input) {
    var parsedDate = 'Invalid Date';
    if (input !== '') {
        var format = getDateFormatStringFromLocale(localeString);
        var input_parts = input.match(/(\d+)/g);
        try {
            var i = 0;
            var format_pos = {};
            format.replace(/(YYYY|DD|MM)/g, function (part) { format_pos[part] = i++; });
            var year = parseInt(input_parts[format_pos['YYYY']]);
            var month = parseInt(input_parts[format_pos['MM']]);
            var day = parseInt(input_parts[format_pos['DD']]);
            parsedDate = new Date(year, month - 1, day);
        } catch (error) {
            // in this case we return 'Invalid Date'
        }
    }
    return parsedDate
}

/*
 * parse a Date obj with date and time from input using the locale
 * given in the global variable localeString.
 * returns a date obj or 'Invalid Date' like date.parse
 */
function parseDateTime(input) {
    var parsedDate = 'Invalid Date';
    if (input !== '') {
        var inputParts = [];
        if (input.includes(" at ")) {
            inputParts = input.split('at');
        } else {
            inputParts[0] = input.substring(0,10);
            inputParts[1] = input.substring(11);
        }
        parsedDate = parseDate(inputParts[0].trim());
        if (parsedDate == 'Invalid Date') {
            return parsedDate;
        }
        var timeStr = inputParts[1].trim();
        var hour = 0;
        var min = 0;
        try {
            hour = parseInt(timeStr.substring(0, 2));
            min = parseInt(timeStr.substring(3, 5));
        } catch (error) {
            hour = 0;
            min = 0;
        }
        parsedDate.setHours(hour);
        parsedDate.setMinutes(min);
        parsedDate.setSeconds(0);
        parsedDate.setMilliseconds(0);
    }
    return parsedDate;
}

function formatDateObj(dateObj, outputType) {
    //outputType can be time, date or timedate

    var options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    if ((outputType == 'date') || (outputType == 'datetime')) {
        var output = dateObj.toLocaleString(localeString, options);
        if (outputType == 'date') {
            return output;
        }
    }
    var outputMinutes = dateObj.getMinutes();
    outputMinutes = addZeroBefore(outputMinutes);
    var outputHours = dateObj.getHours();
    outputHours = addZeroBefore(outputHours);
    var time = outputHours + ':' + outputMinutes;

    if (outputType == 'time') {
        return time;
    } else {
        //this MUST be a datetime outputType!
        if (localeString == 'en-GB') {
            output += ' at ' + time;
        } else {
            output += ' ' + time;
        }
        return output;
    }
}

function createClassFromDate(dateObj, type) {
    //type can be 'before' or 'after'
    var className = type + '-';
    var dateStr = formatDateObj(dateObj, 'date');
    className+= dateStr;
    className = className.replace(/\//g, '-');
    return className;
}

function initDateRangePickers() {
    //init in the past or in the future declarations
    initInThePastDeclarations();
    initInTheFutureDeclarations();
    initDateRanges();
}

function initDateRanges() {

    var dateRangeInputFields = document.getElementsByClassName('date-range');

    for (var i = 0; i < dateRangeInputFields.length; i++) {
        var result = estPartner(dateRangeInputFields[i]);
        var elType = result.elType; //1st or 2nd (for the clicked form input)
        var partnerEl = result.partnerEl; //the partner form input element

        var thisValue = dateRangeInputFields[i].value;

        if (thisValue.length == 10) {
            parseDate(thisValue);
            adjustPartnerClass(elType, partnerEl, thisDate);
        }

    }

}

function adjustPartnerClass(elType, partnerEl, dateObj) {

    if (elType == '1st') {
        //make sure selectable date is BEFORE whatever value the partner el has
        customClassRemove(partnerEl, 'after-');
        var newClassName = createClassFromDate(dateObj, 'after');
        partnerEl.classList.remove('enforce-after');
        partnerEl.classList.add('enforce-after');
        partnerEl.classList.add(newClassName);

    } else {
        //make sure selectable date is AFTER whatever value the partner el has
        customClassRemove(partnerEl, 'before-');
        var newClassName = createClassFromDate(dateObj, 'before');
        partnerEl.classList.remove('enforce-before');
        partnerEl.classList.add('enforce-before');
        partnerEl.classList.add(newClassName);
    }

}

function initInThePastDeclarations() {
    var inThePastEls = document.getElementsByClassName('in-the-past');

    if (inThePastEls.length>0) {

        var dateStr = formatDateObj(todayDate, 'date');
        dateStr = dateStr.replace(/\//g, '-');
        var newClassName = 'before-' + dateStr;

        for (var i = 0; i < inThePastEls.length; i++) {
            inThePastEls[i].classList.add('enforce-before');
            inThePastEls[i].classList.add(newClassName);
        }

        for (var i = 0; i < inThePastEls.length; i++) {
            inThePastEls[i].classList.remove('in-the-past');
        }

    }

}

function initInTheFutureDeclarations() {
    var inTheFutureEls = document.getElementsByClassName('in-the-future');

    if (inTheFutureEls.length>0) {

        var dateStr = formatDateObj(todayDate, 'date');
        dateStr = dateStr.replace(/\//g, '-');
        var newClassName = 'after-' + dateStr;

        for (var i = 0; i < inTheFutureEls.length; i++) {
            inTheFutureEls[i].classList.add('enforce-after');
            inTheFutureEls[i].classList.add(newClassName);
        }

        for (var i = 0; i < inTheFutureEls.length; i++) {
            inTheFutureEls[i].classList.remove('in-the-future');
        }

    }

}

function estPartner(el) {
    for (var i = 0; i < dateRangePickerFields.length; i++) {
        if (dateRangePickerFields[i] == el) {
            var elPos = i+1;
            var elType = (elPos % 2  == 0) ? "2nd" : "1st";
            
            if (elType == '1st') {
                var partnerIndex = elPos;
            } else {
                var partnerIndex = i-1;
            }

            var params = {
                elType,
                partnerEl: dateRangePickerFields[partnerIndex]
            }

            return params;
        }
    }
}

function initDatePickers() {

    initInThePastDeclarations();
    initInTheFutureDeclarations();

    //listen for a datePicker input field getting clicked
    for (var i = 0; i < datePickerFields.length; i++) {
        datePickerFields[i].addEventListener("click", (ev) => {

            targetInputValue = ev.target.value;
            targetInputValueSm = targetInputValue;

            //build a datePickerCalendar and then add it to the page * (taking canvas size into account)
            activeEl = ev.target;
            var elClasses = activeEl.classList;

            if (activeEl.classList.contains('date-range')) {
                activeType = 'date-range-calendar';
            } else {
                activeType = 'datepicker-calendar';
            }

            assumedDate = getDateFromInput();

            if (assumedDate == 'Invalid Date') {
                assumedDate = new Date;
            }

            buildDatePickerCalendar();
        });
    }

}

function enforceBeforeVibes() {
    //get a list of all of the classes that belong to the active element
    var elClasses = activeEl.classList;
    for (var i = 0; i < elClasses.length; i++) {
        if (elClasses[i].length == 17) {
            var strStart = elClasses[i].substring(0, 7);
            if (strStart == 'before-') {
                unavailableAfter = createDateFromClassName(elClasses[i]);
            }
        }

    }
}

function enforceAfterVibes() {
    //get a list of all of the classes that belong to the active element
    var elClasses = activeEl.classList;
    for (var i = 0; i < elClasses.length; i++) {
        if (elClasses[i].length == 16) {
            var strStart = elClasses[i].substring(0, 6);
            if (strStart == 'after-') {
                unavailableBefore = createDateFromClassName(elClasses[i]);
            }
        }

    }
}

function createDateFromClassName(className) {
    //type can be 'before' or 'after'
    var classStr = className.replace('before-', '');
    classStr = className.replace('after-', '');
    return parseDate(classStr);
}

function buildDatePickerCalendar() {

    destroyEls(activeType);
    unavailableBefore = false;
    unavailableAfter = false;

    if (activeEl.classList.contains('enforce-before')) {
        enforceBeforeVibes();
    }

    if (activeEl.classList.contains('enforce-after')) {
        enforceAfterVibes();
    }

    var datePickerCalendar = document.createElement("div");
    datePickerCalendar.setAttribute("class", "datepicker-calendar");

    if (datePickerCanvas == 'large') {
        activeEl.parentNode.insertBefore(datePickerCalendar, activeEl.nextSibling);
    } else {
        //create an overlay
    }

    var datePickerHead = buildDatePickerHead();
    datePickerCalendar.appendChild(datePickerHead);

    //build and populate calendar table
    var datePickerTbl = buildAndPopulateDatePickerTbl();
    datePickerCalendar.appendChild(datePickerTbl);
    activePopUp = datePickerCalendar;

}

function buildDatePickerHead() {

    var datePickerHead = document.createElement("div");
    datePickerHead.setAttribute("class", "datepicker-head");

    var datePickerHeadLeft = document.createElement("div");
    datePickerHead.appendChild(datePickerHeadLeft);

    var datePickerArrowDivLeft = document.createElement("div");
    datePickerArrowDivLeft.setAttribute("onclick", "changeMonth('down')");
    datePickerArrowDivLeft.setAttribute("class", "popup-arrow");

    var flipArrowSpan = document.createElement("span");
    flipArrowSpan.setAttribute("class", "flip-arrow");
    var datePickerNavArrowLeft = document.createTextNode("▸");
    flipArrowSpan.appendChild(datePickerNavArrowLeft);

    datePickerArrowDivLeft.appendChild(flipArrowSpan);
    datePickerHeadLeft.appendChild(datePickerArrowDivLeft);

    var datePickerHeadCenter = document.createElement("div");

    //javascript get month and year from date object

    var currentMonthNum = assumedDate.getMonth(); //getMonth() returns month from 0-11 not 1-12
    var currentMonth = monthNames[currentMonthNum];
    var currentYear = assumedDate.getFullYear();

    var datePickerHeadline = document.createTextNode(currentMonth + " " + currentYear);
    datePickerHeadCenter.appendChild(datePickerHeadline);
    
    datePickerHead.appendChild(datePickerHeadCenter);

    var datePickerHeadRight = document.createElement("div");
    var datePickerArrowDivRight = document.createElement("div");
    datePickerArrowDivRight.setAttribute("onclick", "changeMonth('up')");
    datePickerArrowDivRight.setAttribute("class", "popup-arrow");

    var datePickerNavArrowRight = document.createTextNode("▸");
    datePickerArrowDivRight.appendChild(datePickerNavArrowRight);
    datePickerHeadRight.appendChild(datePickerArrowDivRight);

    datePickerHead.appendChild(datePickerHeadRight);
    return datePickerHead;
}

function buildAndPopulateDatePickerTbl() {

    var datePickerTbl = document.createElement("table");
    datePickerTbl.appendChild(datePickerTblTopRow);

    var monthStartDayNum = getMonthStartDayNum();
    var numDaysInMonth = getNumDaysInMonth();
    var numWeeksThisMonth = Math.ceil(numDaysInMonth/7);

    if ((monthStartDayNum>weekStartsOn) && (numWeeksThisMonth<5)) {
        numWeeksThisMonth = 5;
    }

    if ((numDaysInMonth>29) && (monthStartDayNum>=6)) {
        numWeeksThisMonth++;
    }

    if (weekStartsOn == 1) {
        var boxCounter = 0;
    } else {
        var boxCounter = -1;
    }
    
    var dayCounter = 0;

    var i = 1;
    var isCurrentDay = false;
    var isAvailable = false;
    
    var selectedDate = parseDate(targetInputValueSm);
    if (selectedDate == 'Invalid Date') {
        selectedDate = new Date();
    }
    var selectedDateStr = selectedDate.getFullYear();
    selectedDateStr += "-"+addZeroBefore(selectedDate.getMonth()+1);
    selectedDateStr += "-"+addZeroBefore(selectedDate.getDate());
  
    do {
        //create a week row
        calendarWeekRow = document.createElement("tr");
        calendarWeekRow.setAttribute("class", "tg-datepicker-row");

        for (var colNum = 0; colNum < 7; colNum++) {
            boxCounter++; 
            var calendarTblTd = document.createElement("td");

            if ((boxCounter<monthStartDayNum) || (dayCounter>=numDaysInMonth)) {
                calendarTblTd.setAttribute("class", "empty-day");
                var boxDayNum = ' ';
            } else {
                dayCounter++;
                var boxDayNum = dayCounter;

                //test to see if this box is clickable (available)
                isAvailable = testForIsAvailable(dayCounter);

                if (isAvailable == false) {
                    calendarTblTd.setAttribute("class", "unavailable-day");
                } else {

                    if (isCurrentDay !== true) {
                        isCurrentDay = testForCurrentDay(dayCounter);
                    }
                    
                    if (isCurrentDay == true) {
                        calendarTblTd.setAttribute("class", "day-cell current-day");
                        isCurrentDay = false;
                    } else {
                        calendarTblTd.setAttribute("class", "day-cell");
                    }

                    if (targetInputValue !== '') {
                        boxDayNumLZ = ("0" + boxDayNum).slice(-2);
                        boxMonthNum = ("0" + (assumedDate.getMonth() + 1)).slice(-2);
                        boxYearNum = assumedDate.getFullYear();
                        var unseenBoxValue = boxYearNum + '-' + boxMonthNum + '-' + boxDayNumLZ;
                        if (unseenBoxValue == selectedDateStr) {
                            calendarTblTd.classList.add("selected-day-cell");
                        }
                    }

                    calendarTblTd.setAttribute("onclick", "clickDay('" + boxDayNum + "')")

                }


            }

            var calendarTblTdTxt = document.createTextNode(boxDayNum);
            calendarTblTd.appendChild(calendarTblTdTxt);
            calendarWeekRow.appendChild(calendarTblTd);
        }

        datePickerTbl.appendChild(calendarWeekRow);

      i++;
    }
    while (i <= numWeeksThisMonth);  

    return datePickerTbl;

}

function buildTopRow() {

    if (weekStartsOn == 1) {
        var dayZero = dayNames[0];
        dayNames.shift();
        dayNames.push(dayZero);
    }

    datePickerTblTopRow = document.createElement("tr");
    datePickerTblTopRow.setAttribute("class", "tg-datepicker-row");

    for (var i = 0; i <= 6; i++) {
        var dayLabel = dayNames[i].substring(0, 2);
        var calendarTblTh = document.createElement("th");
        var calendarTblThTxt = document.createTextNode(dayLabel);
        calendarTblTh.appendChild(calendarTblThTxt);
        datePickerTblTopRow.appendChild(calendarTblTh);
    }

    return datePickerTblTopRow;

}

function getMonthStartDayNum() {
    var y = assumedDate.getFullYear(); 
    var m = assumedDate.getMonth();
    var firstDay = new Date(y, m, 1); 
    var monthStartDayNum = firstDay.getDay();

    if (monthStartDayNum == 0) {
        monthStartDayNum = 7;
    }

    return monthStartDayNum;
}

function getNumDaysInMonth() {
    var theMonth = assumedDate.getMonth()+1; // Here January is 0 based
    var theYear = assumedDate.getFullYear();
    return new Date(theYear, theMonth, 0).getDate();
}

function testForIsAvailable(dayCounter) {

    //turn the day (to be tested) into a date object
    var boxDate = new Date(assumedDate.getFullYear(), assumedDate.getMonth(), dayCounter);

    if(typeof unavailableBefore == 'object') {

        if (boxDate<=unavailableBefore) {
            return false;
        }

    }

    if (typeof unavailableAfter == 'object') {

        if (boxDate>=unavailableAfter) {
            return false;
        }

    }

    return true;
}

function testForCurrentDay(dayCounter) {

    var todayStr = todayDate.getDate() + ' ' + todayDate.getMonth() + ' ' + todayDate.getFullYear();
    var assumedDateStr = dayCounter + ' ' + assumedDate.getMonth() + ' ' + assumedDate.getFullYear();

    if (todayStr == assumedDateStr) {
        return true;
    } else {
        return false;
    }

}

function changeMonth(direction) {

    var m = assumedDate.getMonth();

    if (direction == 'down') {
        var newM = m-1;
        assumedDate.setMonth(newM);
    } else {
        var newM = m+1;
        assumedDate.setMonth(newM);
    }

    refreshDatePickerHead();

    if (activeType == 'datetime-picker-calendar') {
        var calendarTbl = document.querySelector(".datetime-picker-calendar table:nth-child(2)");
        calendarTbl.remove();
        var datePickerTbl = buildAndPopulateDatePickerTbl();
        var targetElement = document.querySelector(".datepicker-head");
        targetElement.insertAdjacentElement("afterend", datePickerTbl);
    } else {
        var datePickerCalendar = activePopUp;
        var childNodes = datePickerCalendar.childNodes;
        childNodes[1].remove(); //remove the table with the days    
        //build and populate calendar table   
        var datePickerTbl = buildAndPopulateDatePickerTbl();
        datePickerCalendar.appendChild(datePickerTbl); 
    }
}

function refreshDatePickerHead() {
    var baseElement = document.querySelector(".datepicker-head");
    var targetDiv = baseElement.querySelector("div:nth-child(2)");
    var currentMonthNum = assumedDate.getMonth(); // getMonth() returns month from 0-11 not 1-12
    var currentMonth = monthNames[currentMonthNum];
    var currentYear = assumedDate.getFullYear();
    var datePickerHeadline = currentMonth + ' ' + currentYear;
    targetDiv.innerHTML = datePickerHeadline;
}

function clickDay(dayNum) {

    highlightClickedDayCell(dayNum);
    assumedDate.setDate(dayNum);

    if ((activeType == 'datepicker-calendar') || (activeType == 'date-range-calendar')) {
        var niceDate = formatDateObj(assumedDate, 'date');
    } else {
        var niceDate = formatDateObj(assumedDate, 'datetime');
    }

    //update the textfield so that it has the nice date
    activeEl.value = niceDate;

    if (activeType == 'date-range-calendar') {
        //get the partner date and target pos (1st or 2nd)
        var result = estPartner(activeEl);
        var elType = result.elType; //1st or 2nd (for the clicked form input)
        var partnerEl = result.partnerEl; //the partner form input element
        adjustPartnerClass(elType, partnerEl, assumedDate);
    }

    if (activeType !== 'datetime-picker-calendar') {
        activePopUp.remove();
    }

}

function customClassRemove(targetEl, strStart) {
    var elClasses = targetEl.classList;
    var targetIndex = strStart.length;

    for (var i = 0; i < elClasses.length; i++) {

        if (elClasses[i].length>targetIndex) {
            var classStart = elClasses[i].substring(0, targetIndex);

            if (classStart == strStart) {
                elClasses.remove(elClasses[i]);
            }
        }
        
    }
}

function highlightClickedDayCell(dayNum) {
    var tblCells = document.getElementsByClassName("day-cell");

    for (var i = 0; i < tblCells.length; i++) {
        if (tblCells[i].innerHTML == dayNum) {
            tblCells[i].classList.add("selected-day-cell");
        } else {
            tblCells[i].classList.remove("selected-day-cell");
        }
    }
}

function disableDatePickerInputs(className) {

    //make the input fields (for datePickers) 'disabled'
    var datePickerInputs = document.getElementsByClassName(className);
    var originalValue;
    for (var i = 0; i < datePickerInputs.length; i++) {

        // javascript get character that was pressed
     
        var originalValue = '';
        datePickerInputs[i].addEventListener("focus", (ev) => {
            originalValue = ev.target.value;
        });
        
        datePickerInputs[i].addEventListener("blur", (ev) => {
            if (parseDate(ev.target.value) == 'Invalid Date') {
                ev.target.value = originalValue;
            }  
        });
        

        datePickerInputs[i].addEventListener("keyup", (ev) => {
            var isNumber = /^[0-9]$/i.test(ev.key);
            if ((ev.key == 'Backspace') || (ev.key == 'Delete')) {
                if (ev.target.value.length == 0) {
                    originalValue = ev.target.value;
                } else {
                    ev.target.value = originalValue;    
                }
            } else if (isNumber !== true) {
                ev.target.value = originalValue;
            } else {
                //attempt to extract the year from the form input field
                var extractedYear = attemptExtractYear(ev.target.value);

                if (extractedYear !== false) {
                    //we have a valid year in the form input field
                    assumedDate.setYear(extractedYear);
                    activePopUp.remove();
                }
            }
            
        });

    }

}

function attemptExtractYear(text) {
    var score = 0;
    var extractedYear = '';

    for (var x = 0; x < text.length; x++) {
        var c = text.charAt(x);
        var isNumber = /^[0-9]$/i.test(c);

        if (isNumber == true) {
            score++;
            extractedYear+= c;
        } else {
            score = 0;
            extractedYear = '';
        }

        if (score == 4) {
            return extractedYear;
        }

    }

    return false;
}

function getDateFromInput() {
    value = activeEl.value;
    return parseDate(value);
}

































function destroyEls(className) {
    var targetEls = document.getElementsByClassName(className);
    for (var i = 0; i < targetEls.length; i++) {
        targetEls[i].remove();
    }

    if ((className == 'timepicker-popup') || (className == 'datetime-picker-calendar')) {
        activeTimePickerInputs();
    }
}

function childOf(node, ancestor) {
    var child = node;
    while (child !== null) {
        if (child === ancestor) return true;
        child = child.parentNode;
    }
    return false;   
}

function listenForOffsideClicks(inputClass, popUpClass) {
    body.addEventListener("click", (ev) => {
        //is the clickedTimePickerEl on or within one of our target classes?
        var offSide = isOffside(ev.target, inputClass, popUpClass);
        if (offSide == true) {
            destroyEls(popUpClass);
        }
    });
}

function isOffside(clickedTimePickerEl, inputClass, popUpClass) {

    var offSide = true;

    if ((clickedTimePickerEl.classList.contains(inputClass)) || (clickedTimePickerEl.classList.contains(popUpClass)))  {
        offSide = false;
    } else {
        //let's check to see if is child of one of the target classes
        var targetAncestors = getTargetAncestors(inputClass, popUpClass);

        for (var i = 0; i < targetAncestors.length; i++) {
            
            var isChildOf = childOf(clickedTimePickerEl, targetAncestors[i]);

            if (isChildOf == true) {
                offSide = false;
            }

        }

    }

    return offSide;

}

function getTargetAncestors(inputClass, popUpClass) {
    var targetAncestors = [];
    var targetInputs = document.getElementsByClassName(inputClass);
    for (var i = 0; i < targetInputs.length; i++) {
        targetAncestors.push(targetInputs[i]);
    }

    var targetPopUps = document.getElementsByClassName(popUpClass);
    for (var i = 0; i < targetPopUps.length; i++) {
        targetAncestors.push(targetPopUps[i]);
    }
    return targetAncestors;
}































//time pickers
function initTimePickers() {
    //listen for a time-picker input field getting clicked

    for (var i = 0; i < timePickerFields.length; i++) {

        var timePickerVal = timePickerFields[i].value;

        if (timePickerVal.length == 8) {
            timePickerFields[i].value = timePickerVal.substring(0, timePickerVal.length-3); 
        }

        timePickerFields[i].addEventListener("click", (ev) => {
            clickedTimePickerEl = ev.target;
            activeType = 'timepicker-popup';
            buildTimePickerPopUp(clickedTimePickerEl, false);
        });
    }
}

function addZeroBefore(n) {
    return (n < 10 ? '0' : '') + n;
}

function buildTimePickerPopUp(clickedTimePickerEl, parentCalendar) {

    if (parentCalendar == true) {
        clickedTimePickerEl = parentCalendar;
    }

    destroyEls("timepicker-popup");
    var timePicker = document.createElement("div");
    timePicker.setAttribute("class", "timepicker-popup");

    //build the timePicker table
    var timePickerTbl = document.createElement("table");
    var timePickerTblTopTr = document.createElement("tr");
    timePickerTbl.appendChild(timePickerTblTopTr);
    var timePickerTblTopTh = document.createElement("th");
    timePickerTblTopTh.setAttribute("colspan", "2");
    var tblHeadline = document.createTextNode('Choose Time');
    timePickerTblTopTh.appendChild(tblHeadline);
    timePickerTblTopTr.appendChild(timePickerTblTopTh);
    timePickerTbl.appendChild(timePickerTblTopTr);
    
    //first row
    var tblRow = document.createElement("tr");
    var tblCell = document.createElement("td");
    var tblCellTxt = document.createTextNode('Time');
    tblCell.appendChild(tblCellTxt);
    tblRow.appendChild(tblCell);

    tblCell = document.createElement("td");
    var timeValue = formatDateObj(assumedDate, 'time');
    tblCellTxt = document.createTextNode(timeValue);
    tblCell.appendChild(tblCellTxt);
    tblRow.appendChild(tblCell);

    timePickerTbl.appendChild(tblRow);

    //second row
    tblRow = document.createElement("tr");
    tblCell = document.createElement("td");
    tblCellTxt = document.createTextNode('Hour');
    tblCell.appendChild(tblCellTxt);
    tblRow.appendChild(tblCell);

    tblCell = document.createElement("td");
    var formInput = document.createElement("input");
    formInput.setAttribute("type", "range");
    formInput.setAttribute("min", "0");
    formInput.setAttribute("max", "23");
    formInput.setAttribute("oninput", "updateHour(this.value)");
    formInput.setAttribute("onchange", "updateHour(this.value)");
    formInput.setAttribute("value", currentHour);

    tblCell.appendChild(formInput);
    tblRow.appendChild(tblCell);
    timePickerTbl.appendChild(tblRow);

    //third row
    tblRow = document.createElement("tr");
    tblCell = document.createElement("td");
    tblCellTxt = document.createTextNode('Minute');
    tblCell.appendChild(tblCellTxt);
    tblRow.appendChild(tblCell);

    tblCell = document.createElement("td");
    formInput = document.createElement("input");
    formInput.setAttribute("type", "range");
    formInput.setAttribute("min", "0");
    formInput.setAttribute("max", "59");
    formInput.setAttribute("oninput", "updateMinute(this.value)");
    formInput.setAttribute("onchange", "updateMinute(this.value)");
    formInput.setAttribute("value", currentMinute);
    tblCell.appendChild(formInput);
    tblRow.appendChild(tblCell);
    timePickerTbl.appendChild(tblRow);

    //timePicker buttons row
    tblRow = document.createElement("tr");
    tblRow.setAttribute("class", "timepicker-btns");
    tblCell = document.createElement("td");
    var timePickerBtn1 = document.createElement("button");
    timePickerBtn1.setAttribute("type", "button");
    var btn1Txt = document.createTextNode("Now");
    timePickerBtn1.setAttribute("onclick", "setToNow()")

    timePickerBtn1.appendChild(btn1Txt);
    tblCell.appendChild(timePickerBtn1);
    tblRow.appendChild(tblCell);

    tblCell = document.createElement("td");
    var timePickerBtn2 = document.createElement("button");
    timePickerBtn2.setAttribute("type", "button");
    timePickerBtn2.setAttribute("onclick", "closeTimePicker()")
    var btn2Txt = document.createTextNode("Done");
    timePickerBtn2.appendChild(btn2Txt);
    tblCell.appendChild(timePickerBtn2);
    tblCell.setAttribute("style", "text-align: right;");
    tblRow.appendChild(tblCell);

    timePickerTbl.appendChild(tblRow);
    timePicker.appendChild(timePickerTbl);
    clickedTimePickerEl.parentNode.insertBefore(timePicker, clickedTimePickerEl.nextSibling);

    if (parentCalendar !== false) {
        parentCalendar.appendChild(timePickerTbl);
        parentCalendar.appendChild(timePickerTbl);
        timePickerTbl.classList.add("inner-timepicker");
        timePickerTbl.style.borderCollapse = 'collapse';
        var btnRow = document.querySelector("table.inner-timepicker > tr.timepicker-btns");
        btnRow.classList.remove("timepicker-btns");
    }

    if ((clickedTimePickerEl.value !== '') && (parentCalendar == false)) {
        var timeInputValue = clickedTimePickerEl.value;
        var bits = timeInputValue.split(":");

        if (bits.length == 2) {
            currentHour = bits[0];
            currentMinute = bits[1];
            assumedDate.setHours(currentHour, currentMinute);
            var hourSlider = document.querySelector(".timepicker-popup > table > tr:nth-child(3) > td:nth-child(2) > input[type=range]");
            var minuteSlider = document.querySelector(".timepicker-popup > table > tr:nth-child(4) > td:nth-child(2) > input[type=range]");
            updateTimePickerSliders(hourSlider, minuteSlider);
            updateTimePicker();
        }
    }

    disableTimePickerInputs('time-picker');
}

function updateHour(newHour) {
    currentHour = addZeroBefore(newHour);
    updateTimePicker();
}

function updateMinute(newMinute) {
    currentMinute = addZeroBefore(newMinute);
    updateTimePicker();
}

function updateTimePicker() {

    assumedDate.setHours(currentHour, currentMinute);

    if (activeType == 'datetime-picker-calendar') {
        var timeGuideCell = document.querySelector(".inner-timepicker > tr:nth-child(2) > td:nth-child(2)");
        var cellInnerHTML = activeEl.value;
        
        //format the date and time, then add to the calendar.
        var niceDate = formatDateObj(assumedDate, 'datetime');
    
        //update the textfield so that it has the nice date
        activeEl.value = niceDate;

        //get a nice time and update the time guide
        var timeValue = formatDateObj(assumedDate, 'time');
        var timeGuideCell = document.querySelector(".inner-timepicker > tr:nth-child(2) > td:nth-child(2)");
        timeGuideCell.innerHTML = timeValue;


    } else {
        var timeValue = formatDateObj(assumedDate, 'time');
        var timeGuideCell = document.querySelector(".timepicker-popup > table > tr:nth-child(2) > td:nth-child(2)");
        timeGuideCell.innerHTML = timeValue;
        clickedTimePickerEl.value = timeValue; 
    }
}

function setToNow() {
    //create a new time object to represent 'now' (assumedDate!==now)
    assumedDate = new Date;

    currentHour = assumedDate.getHours();
    currentMinute = assumedDate.getMinutes();
    currentHour = addZeroBefore(currentHour);
    currentMinute = addZeroBefore(currentMinute);
    assumedDate.setHours(currentHour, currentMinute);

    if (activeType == 'datetime-picker-calendar') {
        var hourSlider = document.querySelector(".inner-timepicker > tr:nth-child(3) > td:nth-child(2) > input[type=range]");
        var minuteSlider = document.querySelector(".inner-timepicker > tr:nth-child(4) > td:nth-child(2) > input[type=range]");
        refreshDatePickerHead();
        var calendarTbl = document.querySelector(".datetime-picker-calendar table:nth-child(2)");
        calendarTbl.remove();
        var datePickerTbl = buildAndPopulateDatePickerTbl();
        var targetElement = document.querySelector(".datepicker-head");
        targetElement.insertAdjacentElement("afterend", datePickerTbl);
    } else {
        var hourSlider = document.querySelector(".timepicker-popup > table > tr:nth-child(3) > td:nth-child(2) > input[type=range]");
        var minuteSlider = document.querySelector(".timepicker-popup > table > tr:nth-child(4) > td:nth-child(2) > input[type=range]");
    }
    
    updateTimePickerSliders(hourSlider, minuteSlider);
    updateTimePicker();
}

function updateTimePickerSliders(hourSlider, minuteSlider) {
    hourSlider.value = currentHour;
    minuteSlider.value = currentMinute;
}

function closeTimePicker() {
    if (activeType == 'datetime-picker-calendar') {
        destroyEls("datetime-picker-calendar");
    } else {
        destroyEls("timepicker-popup");
    }
}

function disableTimePickerInputs() {
    for (var i = 0; i < timePickerFields.length; i++) {
        timePickerFields[i].disabled = true;
    }
}

function activeTimePickerInputs() {
    //gets called after timePicker/datetime-picker-calendar removed
    setTimeout(() => {
        for (var i = 0; i < timePickerFields.length; i++) {
            timePickerFields[i].disabled = false;
        }
    }, 700);
}

function buildDateTimePickerCalendar() {
    destroyEls(activeType);
    var dateTimePickerCalendar = document.createElement("div");
    dateTimePickerCalendar.setAttribute("class", "datetime-picker-calendar");

    if (datePickerCanvas == 'large') {
        activeEl.parentNode.insertBefore(dateTimePickerCalendar, activeEl.nextSibling);
    }

    var dateTimePickerHead = buildDatePickerHead();
    dateTimePickerCalendar.appendChild(dateTimePickerHead);

    //build and populate calendar table
    var datePickerTbl = buildAndPopulateDatePickerTbl();
    dateTimePickerCalendar.appendChild(datePickerTbl);
    activePopUp = dateTimePickerCalendar;  
    buildTimePickerPopUp(activeEl, dateTimePickerCalendar);
}

function initDateTimePickers() {

    initInThePastDeclarations();
    initInTheFutureDeclarations();

    //listen for a datetimePicker input field getting clicked
    for (var i = 0; i < dateTimePickerFields.length; i++) {
        dateTimePickerFields[i].addEventListener("click", (ev) => {
            //build a datePickerCalendar and then add it to the page * (taking canvas size into account)
            activeEl = ev.target;
            activeType = 'datetime-picker-calendar';
            targetInputValue = activeEl.value;
            targetInputValueSm = targetInputValue.substring(0,10);

            unavailableBefore = '';
            unavailableAfter = '';

            assumedDate = parseDateTime(targetInputValue);
            if (assumedDate == 'Invalid Date') {
                assumedDate = new Date;
            }
            clickedTimePickerEl = ev.target;
            buildDateTimePickerCalendar();
        });
    }

}
