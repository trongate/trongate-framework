let trongateDateTimeObj = {
  weekStartsOn: 0,
  activePopUp: {},
  activeType: "",
  datePickerCalendar: {},
  assumedDate: new Date(),
  currentHour: 0,
  currentMinute: 0,
  todayDate: new Date(),
  parsedInputDate: null,
  thisDateFormat: "",
  thisLocaleString: "",
  originalInputValue: "",
  datePickerTblTopRow: {},
  dayNames: [],
  monthNames: [],
};

// *** Trongate Time Date Initialisation START ***
async function tgdtEstablishTrongateBaseUrl() {
  return new Promise((resolve) => {
    // Get the current full URL
    const currentUrl = window.location.href;

    // Check if 'localhost' is present in the URL
    const isLocalhost = currentUrl.includes("://localhost");

    // Split the string by '://'
    const parts = currentUrl.split("://");

    // Extract the protocol
    const protocol = parts[0];

    // Extract the path part after '://'
    const pathAfterProtocol = parts[1];

    // Split the path part by '/'
    const pathParts = pathAfterProtocol.split("/");

    // Determine the number of segments to join based on isLocalhost
    const segmentsToJoin = isLocalhost ? 2 : 1;

    // Take the protocol and join it with everything up to and including the specified number of forward slashes
    tgdtBaseUrl =
      protocol + "://" + pathParts.slice(0, segmentsToJoin).join("/") + "/";
    resolve(tgdtBaseUrl);
  });
}

async function tgdtFetchDefaultDateFormats(tgdtBaseUrl) {
  return new Promise((resolve) => {
    const targetUrl = `${tgdtBaseUrl}dateformat`;
    const http = new XMLHttpRequest();

    // Handle errors
    http.onerror = function () {
      console.error("Error fetching data from:", targetUrl);
      resolve(
        '{"default_date_format":"mm/dd/yyyy","default_locale_str":"en-US"}'
      );
    };

    http.open("get", targetUrl);
    http.setRequestHeader("Content-type", "application/json");
    http.send();

    http.onload = function () {
      if (http.status === 200) {
        resolve(http.responseText);
      } else {
        console.error("Unexpected HTTP status:", http.status);
        resolve(
          '{"default_date_format":"mm/dd/yyyy","default_locale_str":"en-US"}'
        );
      }
    };
  });
}

trongateDateTimeObj.convertDateObjectToLongStr = function () {
  const dateObject = trongateDateTimeObj.assumedDate;

  if (!(dateObject instanceof Date) || isNaN(dateObject.getTime())) {
    return ""; // Return empty string if not a valid date object
  }

  const day = String(dateObject.getDate()).padStart(2, "0");
  const month = String(dateObject.getMonth() + 1).padStart(2, "0"); // Month is zero-indexed
  const year = dateObject.getFullYear();

  const hours = String(dateObject.getHours()).padStart(2, "0");
  const minutes = String(dateObject.getMinutes()).padStart(2, "0");

  const delimiter = trongateDateTimeObj.thisDateFormat.includes("/")
    ? "/"
    : "-"; // Determine the delimiter based on the date format

  let formattedDateStr = "";

  switch (trongateDateTimeObj.thisDateFormat) {
    case "dd/mm/yyyy":
      formattedDateStr = `${day}${delimiter}${month}${delimiter}${year}`;
      break;
    case "dd-mm-yyyy":
      formattedDateStr = `${day}-${month}-${year}`;
      break;
    case "mm/dd/yyyy":
      formattedDateStr = `${month}${delimiter}${day}${delimiter}${year}`;
      break;
    case "mm-dd-yyyy":
      formattedDateStr = `${month}-${day}-${year}`;
      break;
    default:
      return ""; // Invalid format
  }

  return `${formattedDateStr}, ${hours}:${minutes}`;
};

trongateDateTimeObj.convertLongStrToDateObject = function (dateTimeString) {
  if (!dateTimeString) {
    return new Date(); // Return default value for empty input
  }

  const parsedDate = trongateDateTimeObj.parseDateFromInput(dateTimeString);

  if (parsedDate instanceof Date && !isNaN(parsedDate.getTime())) {
    return parsedDate;
  } else {
    return new Date(); // Return default value if parsing fails
  }
};

trongateDateTimeObj.convertTimeStrToDateObject = function (timeString) {
  if (!timeString) {
    return new Date(); // Return default value for empty input
  }

  const currentTime = new Date(); // Get current date-time

  // Extract current day, month, and year
  const currentYear = currentTime.getFullYear();
  const currentMonth = currentTime.getMonth();
  const currentDay = currentTime.getDate();

  // Extract hours and minutes from the time string
  const [hours, minutes] = timeString.split(":").map((num) => parseInt(num));

  // Create a new Date object by assuming the current date along with the provided time
  const dateObjectWithTime = new Date(
    currentYear,
    currentMonth,
    currentDay,
    hours,
    minutes
  );

  // Check if the created date object is valid
  if (
    dateObjectWithTime instanceof Date &&
    !isNaN(dateObjectWithTime.getTime())
  ) {
    return dateObjectWithTime;
  } else {
    return new Date(); // Return default value if creation fails
  }
};

async function tgdtConvertDefaultDateStrToObj(defaultDateFormatsStr) {
  return new Promise((resolve) => {
    try {
      const defaultDatePrefs = JSON.parse(defaultDateFormatsStr);
      const { default_date_format, default_locale_str } = defaultDatePrefs;

      // Check if the string meets the specified conditions
      const isValidDateFormat =
        default_date_format.length === 10 &&
        (default_date_format.substring(0, 2) === "dd" ||
          default_date_format.substring(0, 2) === "mm") &&
        ((default_date_format.substring(0, 2) === "dd" &&
          default_date_format.substring(3, 5) === "mm") ||
          (default_date_format.substring(0, 2) === "mm" &&
            default_date_format.substring(3, 5) === "dd")) &&
        default_date_format.substring(6) === "yyyy";

      const defaultDateFormat = isValidDateFormat
        ? default_date_format
        : "mm/dd/yyyy";
      const defaultLocaleString = isValidDateFormat
        ? default_locale_str
        : "en-US";

      const defaultDateFormatsObj = {
        defaultDateFormat,
        defaultLocaleString,
      };

      resolve(defaultDateFormatsObj);
    } catch (error) {
      console.error("Error parsing JSON:", error);

      const defaultDateFormatsObj = {
        defaultDateFormat: "mm/dd/yyyy",
        defaultLocaleString: "en-US",
      };

      resolve(defaultDateFormatsObj);
    }
  });
}

async function tgdtEstablishDayNames() {
  return new Promise((resolve) => {
    const date = new Date();
    const dayNames = [];

    // Set the day to the appropriate starting day (Sunday or Monday)
    date.setDate(
      date.getDate() - date.getDay() + trongateDateTimeObj.weekStartsOn
    );

    for (let i = 0; i < 7; i++) {
      dayNames.push(
        date.toLocaleDateString(trongateDateTimeObj.thisLocaleString, {
          weekday: "long",
        })
      );
      date.setDate(date.getDate() + 1);
    }

    trongateDateTimeObj.dayNames = dayNames;
    resolve();
  });
}

async function tgdtEstablishMonthNames() {
  return new Promise((resolve) => {
    const date = new Date();
    const monthNames = [];

    for (let i = 0; i < 12; i++) {
      monthNames.push(
        date.toLocaleDateString(trongateDateTimeObj.thisLocaleString, {
          month: "long",
        })
      );
      date.setMonth(date.getMonth() + 1);
    }

    trongateDateTimeObj.monthNames = monthNames;
    resolve(trongateDateTimeObj.monthNames);
  });
}

async function tgdtEstablishDatePickerTblTopRow() {
  return new Promise((resolve) => {
    // Adjust day names order based on weekStartsOn
    const adjustedDayNames = [...trongateDateTimeObj.dayNames];

    trongateDateTimeObj.datePickerTblTopRow = document.createElement("tr");
    trongateDateTimeObj.datePickerTblTopRow.setAttribute(
      "class",
      "tg-datepicker-row"
    );

    // Build top row with adjusted day names
    adjustedDayNames.forEach((dayLabel) => {
      const abbreviatedDay = dayLabel.substring(0, 2);
      const calendarTblTh = document.createElement("th");
      const calendarTblThTxt = document.createTextNode(abbreviatedDay);
      calendarTblTh.appendChild(calendarTblThTxt);
      trongateDateTimeObj.datePickerTblTopRow.appendChild(calendarTblTh);
    });

    resolve();
  });
}

trongateDateTimeObj.removeClassFromElement = function (className) {
  const elements = document.querySelectorAll(`.${className}`);

  elements.forEach((element) => {
    element.classList.remove(className);
  });
};
// *** Trongate Time Date Initialisation FINISH ***
//--------------------------------------------------

// *** Trongate Time Utility Functions START ***

trongateDateTimeObj.gotActivePopup = function (targetEl) {
  // Is there a pop-up (such as a date-picker) that relates to this element on the page?
  const targetInputClass = trongateDateTimeObj.findTargetClass(
    targetEl,
    "tgtd-input-code-"
  );

  if (!targetInputClass) {
    return false;
  }

  const excludePopupClass = targetInputClass.replace("-input-", "-popup-");
  const existingPopupEl = document.getElementsByClassName(excludePopupClass);

  return existingPopupEl.length > 0;
};

trongateDateTimeObj.findTargetClass = function (element, startsWith) {
  const classes = element.classList;

  for (let i = 0; i < classes.length; i++) {
    const currentClass = classes[i];
    if (currentClass.startsWith(startsWith)) {
      return currentClass;
    }
  }

  return null;
};

trongateDateTimeObj.generateElementCodes = function () {
  const randStr = trongateDateTimeObj.generateRandomString();
  const inputCode = "tgtd-input-code-" + randStr;
  const popupCode = inputCode.replace("-input-", "-popup-");

  const elementCodes = {
    inputCode,
    popupCode,
  };

  return elementCodes;
};

trongateDateTimeObj.generateRandomString = function () {
  const characters = "abcdefghijklmnopqrstuvwxyz0123456789";
  let randomString = "";

  for (let i = 0; i < 6; i++) {
    const randomIndex = Math.floor(Math.random() * characters.length);
    randomString += characters.charAt(randomIndex);
  }

  return randomString;
};

trongateDateTimeObj.destroyElements = function (
  className,
  exclusionClass = ""
) {
  const targetEls = document.getElementsByClassName(className);

  for (let i = targetEls.length - 1; i >= 0; i--) {
    const currentElement = targetEls[i];

    // Check if exclusionClass is provided and if the current element has the exclusionClass
    if (
      exclusionClass !== "" &&
      currentElement.classList.contains(exclusionClass)
    ) {
      continue;
    }

    const targetPopupClass = trongateDateTimeObj.findTargetClass(
      currentElement,
      "tgtd-popup-code-"
    );
    const inputClass = targetPopupClass.replace("-popup-", "-input-");
    trongateDateTimeObj.removeClassFromElement(inputClass);
    currentElement.remove();
  }

  // Let's also remove any overlays that might be on the page.
  const timeDateOverlays = document.querySelectorAll(
    ".trongate-time-date-overlay"
  );
  for (let j = timeDateOverlays.length - 1; j >= 0; j--) {
    timeDateOverlays[j].remove();
  }
};

trongateDateTimeObj.parseDateFromInput = function (inputValue) {
  // Accepts a date string in either UK (dd-mm-yyyy) or US (mm/dd/yyyy) format with optional time (hh:mm)
  // Returns a JavaScript Date object parsed from the input value, or null if parsing fails.
  const mmIndex = trongateDateTimeObj.thisDateFormat.indexOf("mm");

  let extractedDay;
  let extractedMonth;
  let extractedYear = inputValue.substring(6, 10);

  let hasTime = false;
  let extractedHours = 0;
  let extractedMinutes = 0;

  const timeIndex = inputValue.indexOf(", ");

  if (timeIndex !== -1) {
    const timeString = inputValue.substring(timeIndex + 2); // Extract the time string after ', '
    const timeComponents = timeString.split(":");
    if (timeComponents.length === 2) {
      const parsedHours = parseInt(timeComponents[0]);
      const parsedMinutes = parseInt(timeComponents[1]);
      if (!isNaN(parsedHours) && !isNaN(parsedMinutes)) {
        extractedHours = parsedHours;
        extractedMinutes = parsedMinutes;
        hasTime = true;
      }
    }
  }

  if (mmIndex === 3) {
    // Assume UK date format (e.g., 25-12-2045)
    extractedDay = inputValue.substring(0, 2);
    extractedMonth = inputValue.substring(3, 5);
  } else {
    // Assume (default) US date format (e.g., 12/25/2045)
    extractedDay = inputValue.substring(3, 5);
    extractedMonth = inputValue.substring(0, 2);
  }

  try {
    // Attempt to create a Date object from extracted values
    const parsedDate = new Date(
      parseInt(extractedYear),
      parseInt(extractedMonth) - 1,
      parseInt(extractedDay),
      extractedHours,
      extractedMinutes
    );

    // Check if the parsed date is valid
    if (isNaN(parsedDate.getTime())) {
      return null; // Return null if the date is invalid
    }

    return parsedDate; // Return the valid date
  } catch (error) {
    return null; // Return null on error
  }
};

trongateDateTimeObj.buildDatePickerHead = function () {
  const datePickerHead = document.createElement("div");
  datePickerHead.classList.add("datepicker-head");

  const datePickerHeadLeft = document.createElement("div");
  datePickerHead.appendChild(datePickerHeadLeft);

  const datePickerArrowDivLeft = document.createElement("div");
  datePickerArrowDivLeft.addEventListener("click", () =>
    trongateDateTimeObj.changeMonth("down")
  );
  datePickerArrowDivLeft.classList.add("popup-arrow");

  const flipArrowSpanLeft = document.createElement("span");
  flipArrowSpanLeft.classList.add("flip-arrow");
  flipArrowSpanLeft.appendChild(document.createTextNode("▸"));
  datePickerArrowDivLeft.appendChild(flipArrowSpanLeft);
  datePickerHeadLeft.appendChild(datePickerArrowDivLeft);

  const datePickerHeadCenter = document.createElement("div");
  const datePickerHeadlineText = trongateDateTimeObj.getCalendarHeadText();
  datePickerHeadCenter.appendChild(
    document.createTextNode(datePickerHeadlineText)
  );
  datePickerHead.appendChild(datePickerHeadCenter);

  const datePickerHeadRight = document.createElement("div");
  const datePickerArrowDivRight = document.createElement("div");
  datePickerArrowDivRight.addEventListener("click", () =>
    trongateDateTimeObj.changeMonth("up")
  );
  datePickerArrowDivRight.classList.add("popup-arrow");

  const datePickerNavArrowRight = document.createTextNode("▸");
  datePickerArrowDivRight.appendChild(datePickerNavArrowRight);
  datePickerHeadRight.appendChild(datePickerArrowDivRight);
  datePickerHead.appendChild(datePickerHeadRight);

  return datePickerHead;
};

trongateDateTimeObj.getCalendarHeadText = function () {
  return new Intl.DateTimeFormat(trongateDateTimeObj.thisLocaleString, {
    month: "long",
    year: "numeric",
  }).format(trongateDateTimeObj.assumedDate);
};

trongateDateTimeObj.buildAndPopulateDatePickerTbl = function () {
  let monthStartDayNum = trongateDateTimeObj.getMonthStartDayNum();

  if (trongateDateTimeObj.weekStartsOn === 0) {
    monthStartDayNum++;
  }

  const numDaysInMonth = trongateDateTimeObj.getNumDaysInMonth();

  // Calculate the number of days needed to complete the first week
  const remainingDaysInFirstWeek = 7 - (monthStartDayNum - 1);

  // Calculate the total number of weeks needed
  const totalDaysAfterFirstWeek = numDaysInMonth - remainingDaysInFirstWeek;
  const numWeeksThisMonth = 1 + Math.ceil(totalDaysAfterFirstWeek / 7);
  const dayNames = trongateDateTimeObj.dayNames;

  let datePickerTbl = document.createElement("table");
  datePickerTbl.appendChild(trongateDateTimeObj.datePickerTblTopRow);

  // The following variables help to determine 'current-day' class usage.
  const assumedYear = trongateDateTimeObj.assumedDate.getFullYear();
  const assumedMonth = trongateDateTimeObj.assumedDate.getMonth();

  const nowYear = trongateDateTimeObj.todayDate.getFullYear();
  const nowMonth = trongateDateTimeObj.todayDate.getMonth();
  const nowDay = trongateDateTimeObj.todayDate.getDate(); // This returns the day number (1-31)
  const checkForNowDay =
    assumedYear === nowYear && assumedMonth === nowMonth ? true : false;

  // The following variables help to determine 'selected-day-cell' class usage.
  let parsedDay = 0;
  let checkForSelectedDay = false;
  if (trongateDateTimeObj.parsedInputDate !== null) {
    const parsedYear = trongateDateTimeObj.parsedInputDate.getFullYear();
    const parsedMonth = trongateDateTimeObj.parsedInputDate.getMonth();

    parsedDay = trongateDateTimeObj.parsedInputDate.getDate();

    if (assumedYear === parsedYear && assumedMonth === parsedMonth) {
      checkForSelectedDay = true;
    }
  }

  let boxCounter = 0;
  let dayCounter = 0;

  for (let tblRowIndex = 0; tblRowIndex < numWeeksThisMonth; tblRowIndex++) {
    // Create a table row.
    const calendarTblRow = document.createElement("tr");
    datePickerTbl.appendChild(calendarTblRow);

    for (let dayCellIndex = 0; dayCellIndex < dayNames.length; dayCellIndex++) {
      boxCounter++;
      const calendarTblTd = document.createElement("td");

      if (boxCounter < monthStartDayNum || dayCounter >= numDaysInMonth) {
        calendarTblTd.classList.add("empty-day");
        calendarTblTd.innerHTML = "&nbsp;";
      } else {
        dayCounter++;
        calendarTblTd.innerHTML = dayCounter;
        calendarTblTd.addEventListener("click", (ev) =>
          trongateDateTimeObj.clickDay(ev.target)
        );
      }

      // Check if the calendarTblTd does not contain the 'empty-day' class
      const isNotEmptyDay = !calendarTblTd.classList.contains("empty-day");
      const isCurrentDay = checkForNowDay && dayCounter === nowDay;
      if (isCurrentDay && isNotEmptyDay) {
        calendarTblTd.classList.add("current-day");
      }

      const isSelectedDay = checkForSelectedDay && dayCounter === parsedDay;

      if (isSelectedDay && isNotEmptyDay) {
        calendarTblTd.classList.add("selected-day-cell");
      }

      calendarTblRow.appendChild(calendarTblTd);
    }
  }

  trongateDateTimeObj.removeEmptyRows(datePickerTbl);
  return datePickerTbl;
};

trongateDateTimeObj.estMonthType = function () {
  const assumedYear = trongateDateTimeObj.assumedDate.getFullYear();
  const assumedMonth = trongateDateTimeObj.assumedDate.getMonth();

  const nowYear = trongateDateTimeObj.todayDate.getFullYear();
  const nowMonth = trongateDateTimeObj.todayDate.getMonth();

  // Getting date representations for the start of the month using assumedMonth and assumedYear
  const startOfMonthAssumed = new Date(assumedYear, assumedMonth, 1);

  // Getting date representations for the start of the month using nowMonth and nowYear
  const startOfMonthNow = new Date(nowYear, nowMonth, 1);

  let monthType;

  if (startOfMonthNow < startOfMonthAssumed) {
    monthType = "futureMonth";
  } else if (startOfMonthNow > startOfMonthAssumed) {
    monthType = "pastMonth";
  } else {
    monthType = "currentMonth";
  }

  return monthType;
};

trongateDateTimeObj.makeAllCellsUnavailable = function (datePickerTbl) {
  const tableCells = datePickerTbl.querySelectorAll("td");
  for (let i = 0; i < tableCells.length; i++) {
    tableCells[i].classList.add("unavailable-day");
  }
};

trongateDateTimeObj.enforceInTheFuture = function (
  datePickerInput,
  datePickerTbl
) {
  if (datePickerInput.classList.contains("in-the-future")) {
    const monthType = trongateDateTimeObj.estMonthType();

    if (monthType === "pastMonth") {
      // Make all table cells unavailable
      trongateDateTimeObj.makeAllCellsUnavailable(datePickerTbl);
    } else if (monthType === "currentMonth") {
      const tableCells = datePickerTbl.querySelectorAll("td");
      const today = new Date();

      tableCells.forEach((cell) => {
        const cellContent = parseInt(cell.textContent);

        if (!isNaN(cellContent) && cellContent > 0) {
          const cellDate = new Date(
            today.getFullYear(),
            today.getMonth(),
            cellContent
          );

          if (cellDate <= today || cell.classList.contains("current-day")) {
            cell.classList.add("unavailable-day");
          }
        }
      });
    }
  }

  return datePickerTbl;
};

trongateDateTimeObj.enforceInThePast = function (
  datePickerInput,
  datePickerTbl
) {
  if (datePickerInput.classList.contains("in-the-past")) {
    const monthType = trongateDateTimeObj.estMonthType();

    if (monthType === "futureMonth") {
      // Make all table cells unavailable
      trongateDateTimeObj.makeAllCellsUnavailable(datePickerTbl);
    } else if (monthType === "currentMonth") {
      const tableCells = datePickerTbl.querySelectorAll("td");
      const today = new Date();

      tableCells.forEach((cell) => {
        const cellContent = parseInt(cell.textContent);

        if (!isNaN(cellContent) && cellContent > 0) {
          const cellDate = new Date(
            today.getFullYear(),
            today.getMonth(),
            cellContent
          );

          if (cellDate >= today || cell.classList.contains("current-day")) {
            cell.classList.add("unavailable-day");
          }
        }
      });
    }
  }

  return datePickerTbl;
};

trongateDateTimeObj.getMonthStartDayNum = function () {
  const y = trongateDateTimeObj.assumedDate.getFullYear();
  const m = trongateDateTimeObj.assumedDate.getMonth();
  const firstDay = new Date(y, m, 1);
  let monthStartDayNum = firstDay.getDay();

  if (monthStartDayNum === 0) {
    monthStartDayNum = 7;
  }

  return monthStartDayNum;
};

trongateDateTimeObj.getNumDaysInMonth = function () {
  const theMonth = trongateDateTimeObj.assumedDate.getMonth(); // Corrected to be 0-based
  const theYear = trongateDateTimeObj.assumedDate.getFullYear();
  return new Date(theYear, theMonth + 1, 0).getDate();
};

trongateDateTimeObj.addZeroBefore = function (n) {
  return (n < 10 ? "0" : "") + n;
};

trongateDateTimeObj.testForIsAvailable = function (dayCounter) {
  // Turn the day (to be tested) into a date object
  const boxDate = new Date(
    trongateDateTimeObj.assumedDate.getFullYear(),
    trongateDateTimeObj.assumedDate.getMonth(),
    dayCounter
  );

  if (typeof unavailableBefore === "object" && boxDate <= unavailableBefore) {
    return false;
  }

  if (typeof unavailableAfter === "object" && boxDate >= unavailableAfter) {
    return false;
  }

  return true;
};

trongateDateTimeObj.testForCurrentDay = function (dayCounter) {
  const todayStr = `${trongateDateTimeObj.todayDate.getDate()} ${trongateDateTimeObj.todayDate.getMonth()} ${trongateDateTimeObj.todayDate.getFullYear()}`;
  const assumedDateStr = `${dayCounter} ${trongateDateTimeObj.assumedDate.getMonth()} ${trongateDateTimeObj.assumedDate.getFullYear()}`;

  return todayStr === assumedDateStr;
};

trongateDateTimeObj.removeEmptyRows = function (targetTable) {
  const rows = targetTable.getElementsByTagName("tr");

  for (let i = rows.length - 1; i >= 0; i--) {
    const cells = rows[i].getElementsByTagName("td");
    const headerCells = rows[i].getElementsByTagName("th");

    // Skip rows with <th> elements
    if (headerCells.length > 0) {
      continue;
    }

    let isEmptyRow = true;

    for (let j = 0; j < cells.length; j++) {
      const cellContent = cells[j].textContent.trim();
      if (
        !cells[j].classList.contains("empty-day") ||
        (cellContent !== "" && cellContent !== " ")
      ) {
        // If a non-empty or non-"empty-day" cell is found, it's not an empty row
        isEmptyRow = false;
        break;
      }
    }

    if (isEmptyRow) {
      // Remove the empty row
      targetTable.deleteRow(i);
    }
  }
};

trongateDateTimeObj.changeMonth = function (direction) {
  const currentMonth = trongateDateTimeObj.assumedDate.getMonth();

  if (direction === "down") {
    const newMonth = currentMonth - 1;
    trongateDateTimeObj.assumedDate.setMonth(newMonth);
  } else {
    const newMonth = currentMonth + 1;
    trongateDateTimeObj.assumedDate.setMonth(newMonth);
  }

  trongateDateTimeObj.refreshDatePickerHead();

  let datePickerTbl;

  if (trongateDateTimeObj.activeType === "datetime-picker-calendar") {
    const calendarTbl = document.querySelector(
      ".datetime-picker-calendar table:nth-child(2)"
    );
    calendarTbl.remove();
    datePickerTbl = trongateDateTimeObj.buildAndPopulateDatePickerTbl();
    const targetElement = document.querySelector(".datepicker-head");
    targetElement.insertAdjacentElement("afterend", datePickerTbl);
  } else {
    trongateDateTimeObj.datePickerCalendar = trongateDateTimeObj.activePopUp;
    const childNodes = trongateDateTimeObj.datePickerCalendar.childNodes;
    childNodes[1].remove(); // remove the table with the days
    // build and populate calendar table
    datePickerTbl = trongateDateTimeObj.buildAndPopulateDatePickerTbl();
    trongateDateTimeObj.datePickerCalendar.appendChild(datePickerTbl);
  }

  datePickerTbl = trongateDateTimeObj.enforceInTheFuture(
    trongateDateTimeObj.activeEl,
    datePickerTbl
  );
  datePickerTbl = trongateDateTimeObj.enforceInThePast(
    trongateDateTimeObj.activeEl,
    datePickerTbl
  );
};

trongateDateTimeObj.refreshDatePickerHead = function () {
  const baseElement = document.querySelector(".datepicker-head");
  const targetDiv = baseElement.querySelector("div:nth-child(2)");
  const datePickerHeadlineText = trongateDateTimeObj.getCalendarHeadText();
  targetDiv.innerHTML = datePickerHeadlineText;
};

trongateDateTimeObj.formatDateObj = function (dateObj, outputType) {
  const date_format = trongateDateTimeObj.thisDateFormat;
  const day = dateObj.getDate();
  const month = dateObj.getMonth() + 1; // Months are zero-based
  const year = dateObj.getFullYear();

  const formatted_date = date_format
    .replace("dd", String(day).padStart(2, "0"))
    .replace("mm", String(month).padStart(2, "0"))
    .replace("yyyy", String(year));

  if (outputType === "date") {
    return formatted_date;
  } else if (outputType === "time") {
    const hours = String(dateObj.getHours()).padStart(2, "0");
    const minutes = String(dateObj.getMinutes()).padStart(2, "0");
    return `${hours}:${minutes}`;
  } else {
    // Output for 'datetime'
    const localizedDateTime = trongateDateTimeObj.convertDateObjectToLongStr();
    return localizedDateTime;
  }
};

// *** Trongate Time Utility Functions FINISH ***
//--------------------------------------------------

trongateDateTimeObj.clickDay = function (clickedEl) {
  const dayCell = clickedEl.closest("td");

  // Stop if cell contains a class of 'unavailable-day'.
  if (dayCell.classList.contains("unavailable-day")) {
    return;
  }

  const dayNum = dayCell.innerText;
  trongateDateTimeObj.assumedDate.setDate(dayNum);

  let niceDate;
  if (
    trongateDateTimeObj.activeType === "datepicker-calendar" ||
    trongateDateTimeObj.activeType === "date-range-calendar"
  ) {
    niceDate = trongateDateTimeObj.formatDateObj(
      trongateDateTimeObj.assumedDate,
      "date"
    );
  } else {
    // For example, when trongateDateTimeObj.activeType is 'datetime-picker-calendar'
    niceDate = trongateDateTimeObj.formatDateObj(
      trongateDateTimeObj.assumedDate,
      "datetime"
    );
  }

  //update the textfield so that it has the nice date
  const activeEl = trongateDateTimeObj.activeEl;
  activeEl.value = niceDate;

  if (trongateDateTimeObj.activeType === "datepicker-calendar") {
    trongateDateTimeObj.destroyElements("datepicker-calendar");
  }
};

trongateDateTimeObj.syncOtherDateTimeProperties = function () {
  const assumedDate = trongateDateTimeObj.assumedDate;
  let currentHour = assumedDate.getHours();
  let currentMinute = assumedDate.getMinutes();
  trongateDateTimeObj.currentHour =
    trongateDateTimeObj.addZeroBefore(currentHour);
  trongateDateTimeObj.currentMinute =
    trongateDateTimeObj.addZeroBefore(currentMinute);
};

trongateDateTimeObj.buildPopupCalendar = function (
  formInputEl,
  calendarParams = {}
) {
  const elementCodes = trongateDateTimeObj.generateElementCodes();

  let popupCalendarClass = "datepicker-calendar";
  if (calendarParams.type === "datetime-picker") {
    popupCalendarClass = "datetime-picker-calendar";
  }

  // Destroy any existing pop-up calendars.
  trongateDateTimeObj.destroyElements(popupCalendarClass);

  // Get the value from the form input field.
  const datePickerValue = formInputEl.value;
  formInputEl.classList.add(elementCodes.inputCode);

  // Is this a valid date value inside the form input field?
  const parsedDate = trongateDateTimeObj.parseDateFromInput(datePickerValue);

  // Set trongateDateTimeObj.assumedDate, based on extracted input value.
  if (parsedDate === null) {
    trongateDateTimeObj.assumedDate = new Date();
  } else {
    trongateDateTimeObj.assumedDate = parsedDate;
    trongateDateTimeObj.parsedInputDate =
      trongateDateTimeObj.parseDateFromInput(datePickerValue);
  }

  trongateDateTimeObj.datePickerCalendar = document.createElement("div");
  trongateDateTimeObj.datePickerCalendar.setAttribute(
    "class",
    popupCalendarClass + " " + elementCodes.popupCode
  );

  const datePickerHead = trongateDateTimeObj.buildDatePickerHead();
  trongateDateTimeObj.datePickerCalendar.appendChild(datePickerHead);

  //build and populate calendar table
  let datePickerTbl = trongateDateTimeObj.buildAndPopulateDatePickerTbl();
  datePickerTbl = trongateDateTimeObj.enforceInTheFuture(
    formInputEl,
    datePickerTbl
  );
  datePickerTbl = trongateDateTimeObj.enforceInThePast(
    formInputEl,
    datePickerTbl
  );
  trongateDateTimeObj.datePickerCalendar.appendChild(datePickerTbl);

  trongateDateTimeObj.activePopUp = trongateDateTimeObj.datePickerCalendar;

  const activeEl = trongateDateTimeObj.activeEl;
  const isMobileDevice = trongateDateTimeObj.isMobileDevice();

  if (isMobileDevice === true) {
    trongateDateTimeObj.createOverlayWithElement(
      trongateDateTimeObj.datePickerCalendar
    );
  } else {
    activeEl.parentNode.insertBefore(
      trongateDateTimeObj.datePickerCalendar,
      activeEl.nextSibling
    );
  }

  if (popupCalendarClass === "datetime-picker-calendar") {
    trongateDateTimeObj.buildTimePickerPopUp(
      formInputEl,
      trongateDateTimeObj.datePickerCalendar
    );
  }
};

trongateDateTimeObj.createOverlayWithElement = function (targetElement) {
  setTimeout(() => {
    // Do we have any existing overlays?
    const existingOverlay = document.querySelector(
      ".trongate-time-date-overlay"
    );
    if (existingOverlay) {
      return;
    }

    // Create the overlay element
    const overlay = document.createElement("div");
    overlay.setAttribute("class", "trongate-time-date-overlay");

    // Style the overlay
    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100%";
    overlay.style.height = "100%";
    overlay.style.backgroundColor = "rgba(0, 0, 0, 0.833)"; // Adjust the alpha value for transparency

    // Add the overlay to the page body
    document.body.appendChild(overlay);

    // Style the popup
    targetElement.style.position = "fixed";
    targetElement.style.top = "50%";
    targetElement.style.left = "50%";
    targetElement.style.transform = "translate(-50%, -50%)";
    document.body.appendChild(targetElement);
  }, 3);
};

trongateDateTimeObj.isMobileDevice = function () {
  return (
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    ) || navigator.maxTouchPoints > 0
  );
};

trongateDateTimeObj.listenForDatePickerClicks = function (datePickerFields) {
  for (let i = 0; i < datePickerFields.length; i++) {
    const targetDatePickerInput = datePickerFields[i].closest(".date-picker");
    trongateDateTimeObj.listenForDatePickerClick(targetDatePickerInput);
  }
};

trongateDateTimeObj.listenForDatePickerClick = function (
  targetDatePickerInput
) {
  targetDatePickerInput.addEventListener("click", (ev) => {
    trongateDateTimeObj.activeEl = ev.target;
    trongateDateTimeObj.activeType = "datepicker-calendar";
    trongateDateTimeObj.originalInputValue = targetDatePickerInput.value;
    const gotActivePopup = trongateDateTimeObj.gotActivePopup(
      targetDatePickerInput
    );
    if (gotActivePopup === true) {
      return;
    } else {
      trongateDateTimeObj.buildPopupCalendar(targetDatePickerInput);
    }
  });
};

trongateDateTimeObj.listenForTimePickerClicks = function (timePickerFields) {
  for (let i = 0; i < timePickerFields.length; i++) {
    const targetTimePickerInput = timePickerFields[i].closest(".time-picker");
    trongateDateTimeObj.listenForTimePickerClick(targetTimePickerInput);
  }
};

trongateDateTimeObj.listenForDateTimePickerClicks = function (
  dateTimePickerFields
) {
  for (let i = 0; i < dateTimePickerFields.length; i++) {
    const targetDateTimePickerInput =
      dateTimePickerFields[i].closest(".datetime-picker");
    trongateDateTimeObj.listenForDateTimePickerClick(targetDateTimePickerInput);
  }
};

trongateDateTimeObj.listenForDateTimePickerClick = function (
  targetDateTimePickerInput
) {
  targetDateTimePickerInput.addEventListener("click", (ev) => {
    trongateDateTimeObj.activeEl = ev.target;
    trongateDateTimeObj.activeType = "datetime-picker-calendar";
    trongateDateTimeObj.originalInputValue = targetDateTimePickerInput.value;
    const gotActivePopup = trongateDateTimeObj.gotActivePopup(
      targetDateTimePickerInput
    );
    if (gotActivePopup === true) {
      return;
    } else {
      const calendarParams = {
        type: "datetime-picker",
      };

      trongateDateTimeObj.buildPopupCalendar(
        targetDateTimePickerInput,
        calendarParams
      );
    }
  });
};

trongateDateTimeObj.listenForTimePickerClick = function (
  targetTimePickerInput
) {
  targetTimePickerInput.readOnly = true;

  targetTimePickerInput.addEventListener("click", (ev) => {
    trongateDateTimeObj.activeEl = ev.target;
    trongateDateTimeObj.activeType = "timepicker-popup";
    trongateDateTimeObj.originalInputValue = targetTimePickerInput.value;
    const gotActivePopup = trongateDateTimeObj.gotActivePopup(
      targetTimePickerInput
    );
    if (gotActivePopup === true) {
      return;
    } else {
      trongateDateTimeObj.buildTimePickerPopUp(targetTimePickerInput);
    }
  });
};

trongateDateTimeObj.buildTimePickerPopUp = function (
  clickedTimePickerEl,
  parentCalendar = null
) {
  const hasParentCalendar = !!parentCalendar;
  const inputValue = clickedTimePickerEl.value.trim();

  // Update assumedDate based on input type
  if (hasParentCalendar) {
    if (inputValue) {
      trongateDateTimeObj.assumedDate =
        trongateDateTimeObj.convertLongStrToDateObject(inputValue);
    }
    trongateDateTimeObj.syncOtherDateTimeProperties();
  } else if (inputValue) {
    // For simple time-picker inputs
    trongateDateTimeObj.assumedDate =
      trongateDateTimeObj.convertTimeStrToDateObject(inputValue);
    trongateDateTimeObj.syncOtherDateTimeProperties();
  }

  // Generate element codes
  const elementCodes = trongateDateTimeObj.generateElementCodes();
  clickedTimePickerEl.classList.add(elementCodes.inputCode);

  // Destroy existing time pickers
  trongateDateTimeObj.destroyElements("timepicker-popup");

  // Create time picker elements
  const timePicker = document.createElement("div");
  timePicker.setAttribute("class", "timepicker-popup");
  timePicker.classList.add(elementCodes.popupCode);

  const timePickerTbl = document.createElement("table");
  timePicker.appendChild(timePickerTbl);

  // Create table header
  const timeHeadline = document.createElement("th");
  timeHeadline.setAttribute("colspan", "2");
  const timeTopText = hasParentCalendar ? "Time" : "Choose Time";
  timeHeadline.appendChild(document.createTextNode(timeTopText));
  const topTr = document.createElement("tr");
  topTr.appendChild(timeHeadline);
  timePickerTbl.appendChild(topTr);

  // Rows data for time picker table
  const rows = [
    [
      "Time",
      trongateDateTimeObj.formatDateObj(
        trongateDateTimeObj.assumedDate,
        "time"
      ),
    ],
    [
      "Hour",
      trongateDateTimeObj.createRangeInput(
        0,
        23,
        trongateDateTimeObj.currentHour,
        "updateHour"
      ),
    ],
    [
      "Minute",
      trongateDateTimeObj.createRangeInput(
        0,
        59,
        trongateDateTimeObj.currentMinute,
        "updateMinute"
      ),
    ],
  ];

  // Create table rows
  rows.forEach((rowData) => {
    const row = document.createElement("tr");
    rowData.forEach((data) => {
      const cell = document.createElement("td");
      cell.innerHTML = data;
      row.appendChild(cell);
    });
    timePickerTbl.appendChild(row);
  });

  // Create buttons row
  const btnRow = document.createElement("tr");
  btnRow.setAttribute("class", "timepicker-btns");

  // Create 'Now' button
  const btnNow = document.createElement("button");
  btnNow.setAttribute("type", "button");
  btnNow.appendChild(document.createTextNode("Now"));
  btnNow.setAttribute("class", "alt");
  btnNow.setAttribute("onclick", "trongateDateTimeObj.setToNow()");

  // Create 'Done' button
  const btnDone = document.createElement("button");
  btnDone.setAttribute("type", "button");
  btnDone.appendChild(document.createTextNode("Done"));
  btnDone.setAttribute("onclick", "trongateDateTimeObj.closeTimePicker()");

  const cellNow = document.createElement("td");
  cellNow.appendChild(btnNow);
  btnRow.appendChild(cellNow);

  const cellDone = document.createElement("td");
  cellDone.setAttribute("style", "text-align: right;");
  cellDone.appendChild(btnDone);
  btnRow.appendChild(cellDone);

  timePickerTbl.appendChild(btnRow);

  // Handle mobile or desktop display
  const isMobileDevice = trongateDateTimeObj.isMobileDevice();
  if (isMobileDevice) {
    trongateDateTimeObj.createOverlayWithElement(timePicker);
  } else {
    clickedTimePickerEl.parentNode.insertBefore(
      timePicker,
      clickedTimePickerEl.nextSibling
    );
  }

  // Append to parent calendar if exists
  if (hasParentCalendar) {
    parentCalendar.appendChild(timePickerTbl);
    timePickerTbl.classList.add("inner-timepicker");
    timePickerTbl.style.borderCollapse = "collapse";
  }
};

trongateDateTimeObj.createRangeInput = function (
  min,
  max,
  value,
  onchangeFunction
) {
  const input = document.createElement("input");
  input.setAttribute("type", "range");
  input.setAttribute("min", min);
  input.setAttribute("max", max);
  input.setAttribute(
    "oninput",
    `trongateDateTimeObj.${onchangeFunction}(this.value)`
  );
  input.setAttribute(
    "onchange",
    `trongateDateTimeObj.${onchangeFunction}(this.value)`
  );
  input.setAttribute("value", value);
  return input.outerHTML;
};

trongateDateTimeObj.closeTimePicker = function () {
  if (trongateDateTimeObj.activeType == "datetime-picker-calendar") {
    trongateDateTimeObj.destroyElements("datetime-picker-calendar");
  } else {
    trongateDateTimeObj.destroyElements("timepicker-popup");
  }
};

trongateDateTimeObj.setToNow = function () {
  trongateDateTimeObj.assumedDate = new Date();
  trongateDateTimeObj.syncOtherDateTimeProperties();

  let currentHour = trongateDateTimeObj.assumedDate.getHours();
  let currentMinute = trongateDateTimeObj.assumedDate.getMinutes();
  currentHour = trongateDateTimeObj.addZeroBefore(currentHour);
  currentMinute = trongateDateTimeObj.addZeroBefore(currentMinute);
  trongateDateTimeObj.assumedDate.setHours(currentHour, currentMinute);

  let hourSlider, minuteSlider;

  if (trongateDateTimeObj.activeType == "datetime-picker-calendar") {
    hourSlider = document.querySelector(
      ".inner-timepicker > tr:nth-child(3) > td:nth-child(2) > input[type=range]"
    );
    minuteSlider = document.querySelector(
      ".inner-timepicker > tr:nth-child(4) > td:nth-child(2) > input[type=range]"
    );

    trongateDateTimeObj.refreshDatePickerHead();
    const calendarTbl = document.querySelector(
      ".datetime-picker-calendar table:nth-child(2)"
    );
    calendarTbl.remove();
    const datePickerTbl = trongateDateTimeObj.buildAndPopulateDatePickerTbl();
    const targetElement = document.querySelector(".datepicker-head");
    targetElement.insertAdjacentElement("afterend", datePickerTbl);
  } else {
    hourSlider = document.querySelector(
      ".timepicker-popup > table > tr:nth-child(3) > td:nth-child(2) > input[type=range]"
    );
    minuteSlider = document.querySelector(
      ".timepicker-popup > table > tr:nth-child(4) > td:nth-child(2) > input[type=range]"
    );
  }

  trongateDateTimeObj.updateTimePickerSliders(hourSlider, minuteSlider);
  trongateDateTimeObj.updateTimePicker();
};

trongateDateTimeObj.updateTimePickerSliders = function (
  hourSlider,
  minuteSlider
) {
  hourSlider.value = trongateDateTimeObj.currentHour;
  minuteSlider.value = trongateDateTimeObj.currentMinute;
};

trongateDateTimeObj.updateTimePicker = function () {
  trongateDateTimeObj.assumedDate.setHours(
    trongateDateTimeObj.currentHour,
    trongateDateTimeObj.currentMinute
  );

  if (activeType == "datetime-picker-calendar") {
    const timeGuideCell = document.querySelector(
      ".inner-timepicker > tr:nth-child(2) > td:nth-child(2)"
    );
    const cellInnerHTML = activeEl.value;

    // Format the date and time, then add to the calendar.
    const niceDate = formatDateObj(trongateDateTimeObj.assumedDate, "datetime");

    // Update the textfield so that it has the nice date.
    activeEl.value = niceDate;

    // Get a nice time and update the time guide.
    const timeValue = formatDateObj(trongateDateTimeObj.assumedDate, "time");
    timeGuideCell.innerHTML = timeValue;
  } else {
    const timeValue = formatDateObj(trongateDateTimeObj.assumedDate, "time");
    const timeGuideCell = document.querySelector(
      ".timepicker-popup > table > tr:nth-child(2) > td:nth-child(2)"
    );
    timeGuideCell.innerHTML = timeValue;
    clickedTimePickerEl.value = timeValue;
  }
};

trongateDateTimeObj.addZeroBefore = function (n) {
  return (n < 10 ? "0" : "") + n;
};

trongateDateTimeObj.updateHour = function (newHour) {
  trongateDateTimeObj.currentHour = trongateDateTimeObj.addZeroBefore(newHour);
  trongateDateTimeObj.updateTimePicker();
};

trongateDateTimeObj.updateMinute = function (newMinute) {
  trongateDateTimeObj.currentMinute =
    trongateDateTimeObj.addZeroBefore(newMinute);
  trongateDateTimeObj.updateTimePicker();
};

trongateDateTimeObj.updateTimePicker = function () {
  const { currentHour, currentMinute, activeType, assumedDate } =
    trongateDateTimeObj;

  trongateDateTimeObj.assumedDate.setHours(currentHour, currentMinute);

  if (activeType === "datetime-picker-calendar") {
    const timeGuideCell = document.querySelector(
      ".inner-timepicker > tr:nth-child(2) > td:nth-child(2)"
    );
    const cellInnerHTML = trongateDateTimeObj.activeEl.value;

    const niceDate = trongateDateTimeObj.formatDateObj(assumedDate, "datetime");

    trongateDateTimeObj.activeEl.value = niceDate;

    const timeValue = trongateDateTimeObj.formatDateObj(assumedDate, "time");
    timeGuideCell.innerHTML = timeValue;
  } else {
    const timeValue = trongateDateTimeObj.formatDateObj(assumedDate, "time");
    const timeGuideCell = document.querySelector(
      ".timepicker-popup > table > tr:nth-child(2) > td:nth-child(2)"
    );
    timeGuideCell.innerHTML = timeValue;

    const popupEl = timeGuideCell.closest(".timepicker-popup");
    const targetPopupClass = trongateDateTimeObj.findTargetClass(
      popupEl,
      "tgtd-popup-code-"
    );
    const inputClass = targetPopupClass.replace("-popup-", "-input-");
    const clickedTimePickerEl = document.getElementsByClassName(inputClass)[0];
    clickedTimePickerEl.value = timeValue;
  }
};

//--------------------------------------------------
// Invoke initialisation of defaults then wait for clicks...
async function tgdtInitializeDateTimeConfigurations() {
  try {
    const tgdtBaseUrl = await tgdtEstablishTrongateBaseUrl();
    const defaultDateFormatsStr = await tgdtFetchDefaultDateFormats(
      tgdtBaseUrl
    );
    const defaultDateFormatsObj = await tgdtConvertDefaultDateStrToObj(
      defaultDateFormatsStr
    );

    // Set trongateDateTimeObj.thisDateFormat to whatever the default is.
    trongateDateTimeObj.thisDateFormat =
      defaultDateFormatsObj.defaultDateFormat;

    // Set trongateDateTimeObj.thisLocaleString to whatever the default is.
    trongateDateTimeObj.thisLocaleString =
      defaultDateFormatsObj.defaultLocaleString;

    // Now that we know the date formats, we can...
    await tgdtEstablishDayNames();
    await tgdtEstablishMonthNames();
    await tgdtEstablishDatePickerTblTopRow();
    trongateDateTimeObj.currentHour =
      trongateDateTimeObj.assumedDate.getHours();
    trongateDateTimeObj.currentMinute =
      trongateDateTimeObj.assumedDate.getMinutes();
  } catch (error) {
    console.error(error);
  }
}

window.addEventListener("click", (event) => {
  // Remove any unintended or unwanted date/time related popups.

  const popupPairs = {
    "datepicker-calendar": "date-picker",
    "timepicker-popup": "time-picker",
    "datetime-picker-calendar": "datetime-picker",
  };

  const clickedEl = event.target;

  for (const [popupClass, inputClass] of Object.entries(popupPairs)) {
    const targetPopupClass = "." + popupClass;
    const targetPopups = document.querySelectorAll(targetPopupClass);

    if (targetPopups.length > 0) {
      const containingPopup = clickedEl.closest(targetPopupClass);

      if (!containingPopup) {
        const associatedInputClass = "." + inputClass;
        const associatedInputEl = clickedEl.closest(associatedInputClass);

        let excludePopupClass = "";

        if (associatedInputEl) {
          const targetInputClass = trongateDateTimeObj.findTargetClass(
            associatedInputEl,
            "tgtd-input-code-"
          );
          excludePopupClass = targetInputClass.replace("-input-", "-popup-");
        }

        trongateDateTimeObj.destroyElements(popupClass, excludePopupClass);
      }
    }
  }
});

document.addEventListener("DOMContentLoaded", () => {
  tgdtInitializeDateTimeConfigurations();
});

window.addEventListener("load", (event) => {
  const fieldTypes = {
    "date-picker": "listenForDatePickerClicks",
    "time-picker": "listenForTimePickerClicks",
    "datetime-picker": "listenForDateTimePickerClicks",
  };

  for (const fieldType in fieldTypes) {
    const fields = document.querySelectorAll("." + fieldType);
    if (fields.length > 0) {
      const methodName = fieldTypes[fieldType];
      trongateDateTimeObj[methodName](fields);
    }
  }
});
