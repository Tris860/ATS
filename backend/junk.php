<?php
session_start(); // Must be called at the beginning!

// Store the user agent in a session variable
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

// You can then access it on other pages as long as the session is active
echo "Your user agent is: " . $_SESSION['user_agent'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-time Timetable</title>
    <style>
        :root {
            /* Primary Colors */
            --color-indigo-700: #4338ca;
            --color-indigo-500: #6366f1;
            --color-blue-600: #2563eb;
            --color-blue-700: #1d4ed8;
            --color-blue-500: #3b82f6;
            --color-blue-400: #60a5fa;
            --color-blue-300: #93c5fd;
            --color-blue-50: #eff6ff;
            --color-blue-200: #bfdbfe;
            --color-red-600: #dc2626;
            --color-red-700: #b91c1c;
            --color-red-500: #ef4444;
            --color-green-600: #16a34a;
            --color-green-900: #14532d;
            --color-green-500: #22c55e;

            /* Neutral Colors */
            --color-white: #fff;
            --color-gray-50: #f9fafb;
            --color-gray-100: #f3f4f6;
            --color-gray-200: #e5e7eb;
            --color-gray-300: #d1d5db;
            --color-gray-500: #6b7280;
            --color-gray-600: #4b5563;
            --color-gray-700: #374151;
            --color-gray-800: #333; /* Adjusted from original for body text */
            --color-gray-900: #1f2937; /* Adjusted from original for strong text */
            --color-e0e0e0: #e0e0e0; /* Custom border color */
            --color-black-transparent-10: rgba(0, 0, 0, 0.1);
            --color-black-transparent-06: rgba(0, 0, 0, 0.06);
            --color-black-transparent-03: rgba(0, 0, 0, 0.3);
            --color-black-transparent-006: rgba(0, 0, 0, 0.06); /* for shadow-inner */
            --color-black-transparent-060: rgba(0, 0, 0, 0.6); /* for modal overlay */
            --color-blue-transparent-50: rgba(96, 165, 250, 0.5); /* for focus ring */

            /* New color for deactivated rows */
            --color-deactivated-bg: #f0f0f0; /* Light gray */
            --color-deactivated-text: #a0a0a0; /* Darker gray for text */

            /* Day selection button colors */
            --color-day-button-bg: #e0e7ff; /* Light indigo */
            --color-day-button-text: #4338ca; /* Indigo-700 */
            --color-day-button-hover-bg: #c7d2fe; /* Lighter indigo on hover */
            --color-day-button-active-bg: var(--color-indigo-700);
            --color-day-button-active-text: var(--color-white);
        }

        /* Basic Reset & Font */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(to bottom right, var(--color-blue-50), var(--color-e8eaf6, #e8eaf6)); /* from-blue-50 to-indigo-100 */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align to top for better content flow */
            padding: 20px;
            box-sizing: border-box;
            color: var(--color-gray-800);
        }

        .container {
            max-width: 960px;
            width: 100%;
            background-color: var(--color-white);
            box-shadow: 0 10px 25px var(--color-black-transparent-10), 0 5px 10px var(--color-black-transparent-06); /* shadow-2xl */
            border-radius: 12px; /* rounded-xl */
            padding: 32px; /* p-8 */
            border: 1px solid var(--color-e0e0e0); /* border-gray-200 */
            box-sizing: border-box;
        }

        h1 {
            font-size: 36px; /* text-4xl */
            font-weight: 800; /* font-extrabold */
            text-align: center;
            color: var(--color-indigo-700); /* text-indigo-700 */
            margin-bottom: 32px; /* mb-8 */
            letter-spacing: -0.025em; /* tracking-tight */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        h1 svg {
            margin-right: 12px; /* mr-3 */
            color: var(--color-indigo-500); /* text-indigo-500 */
        }

        .user-id-display {
            text-align: center;
            margin-bottom: 24px; /* mb-6 */
            font-size: 14px; /* text-sm */
            color: var(--color-gray-600); /* text-gray-600 */
        }

        .user-id-display span {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace; /* font-mono */
            background-color: var(--color-gray-100); /* bg-gray-100 */
            padding: 4px 8px; /* px-2 py-1 */
            border-radius: 6px; /* rounded-md */
            color: var(--color-gray-700); /* text-gray-700 */
        }

        .user-id-display .italic-text {
            margin-top: 4px; /* mt-1 */
            font-size: 12px; /* text-xs */
            font-style: italic;
        }

        /* Day Selection */
        .day-selection {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px; /* gap-2 */
            margin-bottom: 32px; /* mb-8 */
            padding: 16px;
            background-color: var(--color-gray-50);
            border-radius: 8px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }

        .day-button {
            padding: 8px 16px; /* px-4 py-2 */
            border-radius: 6px; /* rounded-md */
            border: 1px solid var(--color-indigo-500);
            background-color: var(--color-day-button-bg);
            color: var(--color-day-button-text);
            font-weight: 600; /* font-semibold */
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
            flex-grow: 1; /* Allow buttons to grow */
            min-width: 80px; /* Minimum width for smaller screens */
            text-align: center;
        }

        .day-button:hover {
            background-color: var(--color-day-button-hover-bg);
        }

        .day-button.active {
            background-color: var(--color-day-button-active-bg);
            color: var(--color-day-button-active-text);
            border-color: var(--color-day-button-active-bg);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Add New Period Form */
        .add-period-form {
            margin-bottom: 40px; /* mb-10 */
            padding: 24px; /* p-6 */
            background-color: var(--color-blue-50); /* bg-blue-50 */
            border-radius: 8px; /* rounded-lg */
            box-shadow: inset 0 2px 4px var(--color-black-transparent-006); /* shadow-inner */
            border: 1px solid var(--color-blue-200); /* border-blue-200 */
        }

        .add-period-form h2 {
            font-size: 24px; /* text-2xl */
            font-weight: 700; /* font-bold */
            color: var(--color-blue-700); /* text-blue-700 */
            margin-bottom: 16px; /* mb-4 */
            display: flex;
            align-items: center;
        }

        .add-period-form h2 svg {
            margin-right: 8px; /* mr-2 */
            color: var(--color-blue-500); /* text-blue-500 */
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px; /* gap-4 */
            align-items: flex-end;
        }

        @media (min-width: 640px) { /* sm: */
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) { /* lg: */
            .form-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .form-field {
            display: flex;
            flex-direction: column;
        }

        .form-field label {
            font-size: 14px; /* text-sm */
            font-weight: 500; /* font-medium */
            color: var(--color-gray-700); /* text-gray-700 */
            margin-bottom: 4px; /* mb-1 */
        }

        .form-field input, .form-field select { /* Added select for day dropdown */
            padding: 12px; /* p-3 */
            border: 1px solid var(--color-gray-300); /* border-gray-300 */
            border-radius: 8px; /* rounded-lg */
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-field input:focus, .form-field select:focus {
            border-color: transparent;
            box-shadow: 0 0 0 2px var(--color-blue-400); /* focus:ring-2 focus:ring-blue-400 */
        }

        .add-button {
            width: 100%;
            background-color: var(--color-blue-600); /* bg-blue-600 */
            color: var(--color-white);
            font-weight: 700; /* font-bold */
            padding: 12px 24px; /* py-3 px-6 */
            border-radius: 8px; /* rounded-lg */
            box-shadow: 0 4px 6px var(--color-black-transparent-10); /* shadow-md */
            transition: background-color 0.3s ease, transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px; /* text-lg */
            cursor: pointer;
            border: none;
        }

        .add-button:hover {
            background-color: var(--color-blue-700); /* hover:bg-blue-700 */
            transform: scale(1.05);
        }

        .add-button svg {
            margin-right: 8px; /* mr-2 */
        }

        /* Timetable Table */
        .table-wrapper {
            overflow-x-auto;
            border-radius: 8px; /* rounded-lg */
            box-shadow: 0 4px 10px var(--color-black-transparent-10); /* shadow-lg */
            border: 1px solid var(--color-e0e0e0); /* border-gray-200 */
        }

        table {
            min-width: 100%;
            border-collapse: collapse;
            background-color: var(--color-white);
        }

        thead {
            background-color: var(--color-gray-50); /* bg-gray-50 */
        }

        th {
            padding: 12px 24px; /* px-6 py-3 */
            text-align: left;
            font-size: 12px; /* text-xs */
            font-weight: 500; /* font-medium */
            color: var(--color-gray-500); /* text-gray-500 */
            text-transform: uppercase;
            letter-spacing: 0.05em; /* tracking-wider */
            border-bottom: 1px solid var(--color-gray-200); /* divide-y divide-gray-200 */
        }

        th:nth-child(4), th:nth-child(5) {
            text-align: center;
        }

        tbody tr {
            transition: background-color 0.15s ease-in-out;
        }

        tbody tr:hover {
            background-color: var(--color-gray-50); /* hover:bg-gray-50 */
        }

        /* New style for deactivated rows */
        tbody tr.deactivated-row {
            background-color: var(--color-deactivated-bg);
            color: var(--color-deactivated-text);
        }

        tbody tr.deactivated-row .period-name,
        tbody tr.deactivated-row .period-start-time,
        tbody tr.deactivated-row .period-end-time {
            color: var(--color-deactivated-text); /* Ensure text color is also gray */
        }


        td {
            padding: 16px 24px; /* px-6 py-4 */
            white-space: nowrap;
            border-bottom: 1px solid var(--color-gray-200); /* divide-y divide-gray-200 */
        }

        td:nth-child(4), td:nth-child(5) {
            text-align: center;
        }

        .loading-message {
            padding: 16px 24px;
            text-align: center;
            color: var(--color-gray-500);
            font-style: italic;
        }

        /* Input fields within table cells for editing */
        .edit-input {
            width: 100%;
            padding: 8px; /* p-2 */
            border: 1px solid var(--color-blue-300); /* border-blue-300 */
            border-radius: 6px; /* rounded-md */
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.1s, box-shadow 0.1s;
        }

        .edit-input:focus {
            box-shadow: 0 0 0 1px var(--color-blue-400); /* focus:ring-1 focus:ring-blue-400 */
        }

        .hidden {
            display: none !important;
        }

        /* Action Buttons */
        .action-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            margin: 0 6px; /* mr-3 */
            transition: transform 0.15s ease-in-out, color 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .action-button:hover {
            transform: scale(1.1);
        }

        .edit-btn { color: var(--color-indigo-600, #4f46e5); /* text-indigo-600 */ }
        .edit-btn:hover { color: var(--color-indigo-900, #3730a3); /* hover:text-indigo-900 */ }

        .save-btn { color: var(--color-green-600); /* text-green-600 */ }
        .save-btn:hover { color: var(--color-green-900); /* hover:text-green-900 */ }

        .delete-btn { color: var(--color-red-600); /* text-red-600 */ }
        .delete-btn:hover { color: var(--color-red-700); /* hover:text-red-900 */ }

        /* Toggle Switch */
        .toggle-switch-label {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        .toggle-switch-checkbox {
            position: absolute;
            left: -9999px; /* sr-only */
        }

        .toggle-switch-background {
            width: 44px; /* w-11 */
            height: 24px; /* h-6 */
            background-color: var(--color-gray-200); /* bg-gray-200 */
            border-radius: 9999px; /* rounded-full */
            transition: background-color 0.2s;
            position: relative;
        }

        .toggle-switch-checkbox:focus-visible + .toggle-switch-background {
            box-shadow: 0 0 0 4px var(--color-blue-transparent-50); /* peer-focus:ring-4 peer-focus:ring-blue-300 */
        }

        .toggle-switch-checkbox:checked + .toggle-switch-background {
            background-color: var(--color-blue-600); /* peer-checked:bg-blue-600 */
        }

        .toggle-switch-handle {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px; /* w-5 */
            height: 20px; /* h-5 */
            background-color: var(--color-white);
            border-radius: 9999px; /* rounded-full */
            transition: transform 0.2s, background-color 0.2s, border 0.2s;
            border: 1px solid var(--color-gray-300); /* border-gray-300 */
        }

        .toggle-switch-checkbox:checked + .toggle-switch-background .toggle-switch-handle {
            transform: translateX(20px); /* peer-checked:after:translate-x-full */
            border-color: var(--color-white); /* peer-checked:after:border-white */
        }

        .toggle-status-icon {
            margin-left: 12px; /* ml-3 */
            font-size: 14px; /* text-sm */
            font-weight: 500; /* font-medium */
            color: var(--color-gray-900); /* text-gray-900 */
            display: inline-flex;
            align-items: center;
        }

        .toggle-status-icon svg {
            display: inline-block;
        }

        .toggle-status-icon .active-icon { color: var(--color-green-500); /* text-green-500 */ }
        .toggle-status-icon .inactive-icon { color: var(--color-red-500); /* text-red-500 */ }

        /* Custom Modal Styles */
        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--color-black-transparent-60);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .custom-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .custom-modal-content {
            background-color: var(--color-white);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px var(--color-black-transparent-03);
            text-align: center;
            max-width: 400px;
            width: 90%;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .custom-modal-overlay.show .custom-modal-content {
            transform: translateY(0);
        }

        .custom-modal-content h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--color-gray-800); /* Adjusted to a neutral dark color */
        }

        .custom-modal-content p {
            font-size: 16px;
            color: var(--color-gray-600); /* Adjusted to a neutral medium color */
            margin-bottom: 25px;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .modal-button {
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s ease;
        }

        .modal-button.confirm {
            background-color: var(--color-red-600); /* red-600 */
            color: var(--color-white);
        }

        .modal-button.confirm:hover {
            background-color: var(--color-red-700); /* red-700 */
        }

        .modal-button.cancel {
            background-color: var(--color-gray-200); /* gray-200 */
            color: var(--color-gray-700); /* gray-700 */
        }

        .modal-button.cancel:hover {
            background-color: var(--color-gray-300); /* gray-300 */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            My Real-time  <?php echo $_SESSION['email'] ?> Timetable
        </h1>

        <!-- Day Selection Buttons -->
        <div class="day-selection" id="daySelection">
            <!-- Buttons will be generated by JavaScript -->
        </div>

        <!-- Add New Period Form -->
        <div class="add-period-form">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/>
                </svg>
                Add New Period
            </h2>
            <div class="form-grid">
                <div class="form-field">
                    <label for="newName">Period Name</label>
                    <input type="text" id="newName" placeholder="e.g., Morning Session" />
                </div>
                <div class="form-field">
                    <label for="newStartTime">Start Time</label>
                    <input type="time" id="newStartTime" />
                </div>
                <div class="form-field">
                    <label for="newEndTime">End Time</label>
                    <input type="time" id="newEndTime" />
                </div>
                <!-- Day selection dropdown for adding a new period -->
                <div class="form-field">
                    <label for="newDay">Day</label>
                    <select id="newDay">
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <button id="addPeriodBtn" class="add-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/>
                    </svg>
                    Add Period
                </button>
            </div>
        </div>

        <!-- Timetable Table -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Period Name</th>
                        <th>Day</th> <!-- New column for Day -->
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th style="text-align: center;">Active</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="timetableBody">
                    <!-- Periods will be rendered here by JavaScript -->
                    <tr>
                        <td colspan="6" class="loading-message" id="loadingMessage">Loading timetable...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Custom Modal for Confirmations -->
    <div id="customModalOverlay" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <h3 id="modalTitle">Confirm Action</h3>
            <p id="modalMessage">Are you sure you want to proceed?</p>
            <div class="modal-buttons">
                <button id="modalConfirmBtn" class="modal-button confirm">Confirm</button>
                <button id="modalCancelBtn" class="modal-button cancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Define your PHP backend endpoint URL
        // IMPORTANT: Replace this with the actual URL to your PHP script!
        const PHP_API_URL = 'your_php_backend_url.php'; // e.g., 'http://localhost/api/timetable.php'

        const timetableBody = document.getElementById('timetableBody');
        const newNameInput = document.getElementById('newName');
        const newStartTimeInput = document.getElementById('newStartTime');
        const newEndTimeInput = document.getElementById('newEndTime');
        const newDaySelect = document.getElementById('newDay'); // Reference to the new day select element
        const addPeriodBtn = document.getElementById('addPeriodBtn');
        const loadingMessage = document.getElementById('loadingMessage');
        const daySelectionContainer = document.getElementById('daySelection');

        // Custom Modal Elements
        const customModalOverlay = document.getElementById('customModalOverlay');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        const modalCancelBtn = document.getElementById('modalCancelBtn');

        let resolveModalPromise; // To resolve the promise when modal is closed
        let currentDay = 'All Days'; // Default to show all days initially

        const daysOfWeek = ['All Days', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Function to show custom modal
        const showCustomModal = (title, message) => {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            customModalOverlay.classList.add('show');

            return new Promise((resolve) => {
                resolveModalPromise = resolve;
            });
        };

        // Event listeners for custom modal buttons
        modalConfirmBtn.onclick = () => {
            customModalOverlay.classList.remove('show');
            resolveModalPromise(true);
        };

        modalCancelBtn.onclick = () => {
            customModalOverlay.classList.remove('show');
            resolveModalPromise(false);
        };

        // Function to render the timetable
        const renderTimetable = (periods) => {
            timetableBody.innerHTML = ''; // Clear existing rows
            if (periods.length === 0) {
                timetableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="loading-message">No periods added for ${currentDay === 'All Days' ? 'this day' : currentDay}.</td>
                    </tr>
                `;
                return;
            }

            periods.forEach(period => {
                const row = document.createElement('tr');
                row.id = `period-${period.id}`;
                // Conditionally add 'deactivated-row' class
                row.className = `transition duration-150 ease-in-out ${period.active ? 'hover:bg-gray-50' : 'deactivated-row'}`;
                row.dataset.editing = 'false'; // Custom data attribute to track editing state

                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="period-name text-gray-900 font-medium">${period.name}</span>
                        <input type="text" value="${period.name}" class="edit-input period-name-input hidden" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="period-day text-gray-700">${period.day}</span>
                        <select class="edit-input period-day-input hidden">
                            ${daysOfWeek.filter(d => d !== 'All Days').map(day => `<option value="${day}" ${period.day === day ? 'selected' : ''}>${day}</option>`).join('')}
                        </select>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="period-start-time text-gray-700">${period.startTime}</span>
                        <input type="time" value="${period.startTime}" class="edit-input period-start-time-input hidden" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="period-end-time text-gray-700">${period.endTime}</span>
                        <input type="time" value="${period.endTime}" class="edit-input period-end-time-input hidden" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <label class="toggle-switch-label">
                            <input type="checkbox" data-id="${period.id}" ${period.active ? 'checked' : ''} class="toggle-switch-checkbox" />
                            <div class="toggle-switch-background">
                                <span class="toggle-switch-handle"></span>
                            </div>
                            <span class="toggle-status-icon">
                                ${period.active ? '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="active-icon"><path d="M8 2v4"/><path d="M16 2v4"/><path d="M21 13V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8"/><path d="M21 13a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v-4a2 2 0 0 0-2-2z"/><path d="M9 18l3 3L22 11"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inactive-icon"><path d="M8 2v4"/><path d="M16 2v4"/><path d="M21 13V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8"/><path d="M21 13a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v-4a2 2 0 0 0-2-2z"/><path d="M14 2v4"/><path d="M3 6h18"/><path d="M3 10h18"/><path d="m17 17-5 5"/><path d="m12 17 5 5"/></svg>'}
                            </span>
                        </label>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <button data-id="${period.id}" class="action-button edit-btn" title="Edit Period">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                        </button>
                        <button data-id="${period.id}" class="action-button save-btn hidden" title="Save Changes">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        </button>
                        <button data-id="${period.id}" class="action-button delete-btn" title="Delete Period">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                        </button>
                    </td>
                `;
                timetableBody.appendChild(row);
            });

            // Re-attach event listeners after rendering
            attachEventListeners();
        };

        // Function to toggle editing mode for a row
        const toggleEditMode = (rowElement, enable) => {
            const spans = rowElement.querySelectorAll('td > span:not(.toggle-status-icon)');
            const inputs = rowElement.querySelectorAll('input.edit-input');
            const selects = rowElement.querySelectorAll('select.edit-input'); // Select elements
            const editBtn = rowElement.querySelector('.edit-btn');
            const saveBtn = rowElement.querySelector('.save-btn');

            if (enable) {
                spans.forEach(span => span.classList.add('hidden'));
                inputs.forEach(input => input.classList.remove('hidden'));
                selects.forEach(select => select.classList.remove('hidden')); // Show select
                editBtn.classList.add('hidden');
                saveBtn.classList.remove('hidden');
                rowElement.dataset.editing = 'true';
            } else {
                spans.forEach(span => span.classList.remove('hidden'));
                inputs.forEach(input => input.classList.add('hidden'));
                selects.forEach(select => select.classList.add('hidden')); // Hide select
                editBtn.classList.remove('hidden');
                saveBtn.classList.add('hidden');
                rowElement.dataset.editing = 'false';
            }
        };

        // Sample data for demonstration purposes, now including 'day'
        let samplePeriods = [
            { id: '1', name: 'Morning Session', day: 'Monday', startTime: '09:00', endTime: '12:00', active: true },
            { id: '2', name: 'Lunch Break', day: 'Monday', startTime: '12:00', endTime: '13:00', active: false },
            { id: '3', name: 'Afternoon Classes', day: 'Monday', startTime: '13:00', endTime: '16:00', active: true },
            { id: '4', name: 'Evening Study', day: 'Tuesday', startTime: '18:00', endTime: '20:00', active: true },
            { id: '5', name: 'Team Meeting', day: 'Wednesday', startTime: '10:00', endTime: '11:00', active: true },
            { id: '6', name: 'Project Work', day: 'Wednesday', startTime: '11:00', endTime: '13:00', active: true },
            { id: '7', name: 'Gym', day: 'Thursday', startTime: '07:00', endTime: '08:00', active: true },
            { id: '8', name: 'Client Call', day: 'Friday', startTime: '14:00', endTime: '15:00', active: false },
            { id: '9', name: 'Weekend Chill', day: 'Saturday', startTime: '10:00', endTime: '18:00', active: true },
            { id: '10', name: 'Family Time', day: 'Sunday', startTime: '14:00', endTime: '20:00', active: true }
        ];

        // Function to fetch periods (now from sample data, filtered by day)
        const fetchPeriods = async () => {
            loadingMessage.classList.remove('hidden');
            // Simulate network delay
            await new Promise(resolve => setTimeout(resolve, 500));
            
            let filteredPeriods = samplePeriods;
            if (currentDay !== 'All Days') {
                filteredPeriods = samplePeriods.filter(period => period.day === currentDay);
            }
            
            // Sort filtered periods by startTime
            filteredPeriods.sort((a, b) => {
                if (a.startTime < b.startTime) return -1;
                if (a.startTime > b.startTime) return 1;
                return 0;
            });

            renderTimetable(filteredPeriods);
            loadingMessage.classList.add('hidden');
        };

        // Function to add a period (updates sample data)
        const addPeriod = async (periodData) => {
            // Simulate network delay
            await new Promise(resolve => setTimeout(resolve, 300));
            
            const newId = String(Math.max(0, ...samplePeriods.map(p => Number(p.id))) + 1); // Ensure max is at least 0
            const newPeriod = { ...periodData, id: newId };
            samplePeriods.push(newPeriod);
            await fetchPeriods(); // Re-render to show new data
            await showCustomModal("Success", "New period added (to sample data).");
        };

        // Function to update a period (updates sample data)
        const updatePeriod = async (id, updatedData) => {
            // Simulate network delay
            await new Promise(resolve => setTimeout(resolve, 300));

            samplePeriods = samplePeriods.map(period =>
                period.id === id ? { ...period, ...updatedData } : period
            );
            await fetchPeriods(); // Re-render to show updated data
            await showCustomModal("Success", "Period updated (in sample data).");
        };

        // Function to delete a period (updates sample data)
        const deletePeriod = async (id) => {
            // Simulate network delay
            await new Promise(resolve => setTimeout(resolve, 300));

            samplePeriods = samplePeriods.filter(period => period.id !== id);
            await fetchPeriods(); // Re-render to show data without deleted item
            await showCustomModal("Success", "Period deleted (from sample data).");
        };

        // Event listener attachment function
        const attachEventListeners = () => {
            // Edit button click handler
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.onclick = (e) => {
                    const row = e.target.closest('tr');
                    toggleEditMode(row, true);
                };
            });

            // Save button click handler
            document.querySelectorAll('.save-btn').forEach(button => {
                button.onclick = async (e) => {
                    const id = e.target.closest('button').dataset.id;
                    const row = e.target.closest('tr');
                    const name = row.querySelector('.period-name-input').value;
                    const day = row.querySelector('.period-day-input').value; // Get day from select
                    const startTime = row.querySelector('.period-start-time-input').value;
                    const endTime = row.querySelector('.period-end-time-input').value;

                    await updatePeriod(id, { name, day, startTime, endTime });
                    toggleEditMode(row, false); // Exit editing mode regardless of success, as fetchPeriods will refresh
                };
            });

            // Delete button click handler
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.onclick = async (e) => {
                    const id = e.target.closest('button').dataset.id;
                    const confirmed = await showCustomModal("Delete Period", "Are you sure you want to delete this period?");
                    if (confirmed) {
                        await deletePeriod(id);
                    }
                };
            });

            // Toggle active checkbox handler
            document.querySelectorAll('.toggle-switch-checkbox').forEach(checkbox => {
                checkbox.onchange = async (e) => {
                    const id = e.target.dataset.id;
                    const newActiveStatus = e.target.checked;
                    await updatePeriod(id, { active: newActiveStatus });
                    // No need to revert checkbox state here, fetchPeriods will redraw correctly
                };
            });
        };

        // Add New Period button click handler
        addPeriodBtn.onclick = async () => {
            const name = newNameInput.value.trim();
            const startTime = newStartTimeInput.value.trim();
            const endTime = newEndTimeInput.value.trim();
            const day = newDaySelect.value; // Get day from the dropdown

            if (!name || !startTime || !endTime || !day) {
                await showCustomModal("Input Error", "Please fill in all fields for the new period.");
                return;
            }

            await addPeriod({
                name: name,
                day: day,
                startTime: startTime,
                endTime: endTime,
                active: true, // New periods are active by default
            });
            // Clear form fields
            newNameInput.value = '';
            newStartTimeInput.value = '';
            newEndTimeInput.value = '';
            newDaySelect.value = 'Monday'; // Reset to default day
        };

        // Function to render day selection buttons
        const renderDaySelectionButtons = () => {
            daySelectionContainer.innerHTML = ''; // Clear existing buttons
            daysOfWeek.forEach(day => {
                const button = document.createElement('button');
                button.textContent = day;
                button.className = `day-button ${currentDay === day ? 'active' : ''}`;
                button.dataset.day = day;
                button.onclick = () => {
                    currentDay = day;
                    // Update active class for buttons
                    document.querySelectorAll('.day-button').forEach(btn => {
                        if (btn.dataset.day === currentDay) {
                            btn.classList.add('active');
                        } else {
                            btn.classList.remove('active');
                        }
                    });
                    fetchPeriods(); // Fetch periods for the newly selected day
                };
                daySelectionContainer.appendChild(button);
            });
        };

        // Initial load of periods and render day selection buttons when the window loads
        window.onload = () => {
            renderDaySelectionButtons(); // Render day buttons first
            fetchPeriods(); // Then fetch periods for the default day
        };
    </script>
</body>
</html>
