
        // Define your PHP backend endpoint URL
        // IMPORTANT: Replace this with the actual URL to your PHP script!
        const PHP_API_URL = 'http://localhost:3000/backend/main.php';

        const timetableBody = document.getElementById('timetableBody');
        const newNameInput = document.getElementById('newName');
        const newStartTimeInput = document.getElementById('newStartTime');
        const newEndTimeInput = document.getElementById('newEndTime');
        const addPeriodBtn = document.getElementById('addPeriodBtn');
        const loadingMessage = document.getElementById('loadingMessage');
        const daySelectionContainer = document.getElementById('daySelection');

        // New element references for navigation and email
        const navTimetableBtn = document.getElementById('navTimetableBtn');
        const navManualBtn = document.getElementById('navManualBtn');
        const navSettingsBtn = document.getElementById('navSettingsBtn');
        const timetableSection = document.getElementById('timetableSection');
        const manualSection = document.getElementById('manualSection');
        const settingsSection = document.getElementById('settingsSection');
        const userEmailDisplay = document.getElementById('userEmailDisplay');

        // New element reference for timetable visibility toggle
        const toggleTimetableVisibility = document.getElementById('toggleTimetableVisibility');

        // New element references for Manual Section
        const ringBellBtn = document.getElementById('ringBellBtn');
        const toggleHardSwitch = document.getElementById('toggleHardSwitch');


        // Custom Modal Elements
        const customModalOverlay = document.getElementById('customModalOverlay');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        const modalCancelBtn = document.getElementById('modalCancelBtn');

        let resolveModalPromise;
        let currentDay = 'All Days';
        let isTimetableEnabled = true; // Global state for timetable visibility
        let isHardSwitchEnabled = true; // Global state for hard switch visibility
        let currentUserId = null; // Store the current user ID after login/session check

        // Corrected daysOfWeek to include Monday
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

        /**
         * Helper function to ensure time is in HH:MM:SS format.
         * It attempts to parse various common time formats and pads with leading zeros.
         * @param {string} timeString - The time string to format.
         * @returns {string} The formatted time string in HH:MM:SS, or an empty string if invalid.
         */
        const formatTimeForInput = (timeString) => {
            if (!timeString) return '';
            
            // Try to match HH:MM:SS, HH:MM, or H:M:S etc.
            const parts = timeString.split(':');
            let hours = parseInt(parts[0], 10);
            let minutes = parseInt(parts[1], 10);
            let seconds = parts[2] ? parseInt(parts[2], 10) : 0; // Default to 0 if seconds part is missing

            if (isNaN(hours) || isNaN(minutes) || isNaN(seconds)) {
                console.warn(`Could not fully parse time string "${timeString}". Returning original or empty.`);
                // Fallback: try to return first 8 characters if it looks like HH:MM:SS
                return timeString.length >= 8 ? timeString.substring(0, 8) : '';
            }

            const formattedHours = String(hours).padStart(2, '0');
            const formattedMinutes = String(minutes).padStart(2, '0');
            const formattedSeconds = String(seconds).padStart(2, '0');
            return `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
        };

        /**
         * Fetches user email and settings from the backend session.
         */
        const fetchUserEmailAndSettings = async () => {
            userEmailDisplay.textContent = 'Loading...'; // Show loading state
            try {
                const response = await fetch(`${PHP_API_URL}?action=get_user_email`); // This action now returns settings too
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('HTTP Error fetching user email/settings:', response.status, response.statusText, errorText);
                    throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText.substring(0, 200)}...`);
                }

                const data = await response.json();
                
                if (data.success) {
                    userEmailDisplay.textContent = data.email || 'N/A'; // Display email or 'N/A' if empty
                    
                    // Update global timetable enabled state and toggle button
                    isTimetableEnabled = data.timetable_enabled;
                    toggleTimetableVisibility.checked = isTimetableEnabled;
                    applyTimetableVisibility(); // Apply visual state

                    // Update global hard switch enabled state and toggle button
                    isHardSwitchEnabled = data.hard_switch_enabled;
                    toggleHardSwitch.checked = isHardSwitchEnabled;
                    applyHardSwitchState(); // Apply visual state for hard switch
                   
                    // Store user ID if available (backend doesn't send it directly in get_user_email, but it's good practice)
                    // For now, we rely on session on backend. If frontend needs userId, backend should provide it.
                    // Assuming currentUserId is managed by login/register on frontend.
                    // If not, and if you need userId on frontend for other actions, you'd fetch it here.
                } else {
                    userEmailDisplay.textContent = 'Error';
                    await showCustomModal("User Data Error", data.message);
                }
            } catch (error) {
                console.error('Network error fetching user email/settings:', error);
                userEmailDisplay.textContent = 'Network Error';
                await showCustomModal("Network Error", "Could not fetch user data. Please check your network connection and backend URL.");
            }
        };
        const fetchTimezone = async () =>{
            const timezoneSelect = document.getElementById('timezoneSelect');
    
            try {
              const response = await fetch(`${PHP_API_URL}?action=get_timezone`);
              const data = await response.json();

               if (data.success === true && data.message) {
                const savedTimezone = data.message.trim();
                  // Find the option with the matching value and set it as selected
                  const optionToSelect = timezoneSelect.querySelector(`option[value="${savedTimezone}"]`);
                  if (optionToSelect) {
                     optionToSelect.selected = true;
                   } else {
                     console.warn('Saved timezone not found in the dropdown list:', data.timezone);
                   }
                 }
                } catch (error) {
                  console.error('Error fetching user timezone:', error);
                }
        }
        /**
         * Applies the visual state of the timetable section based on isTimetableEnabled.
         */
        const applyTimetableVisibility = () => {
            if (isTimetableEnabled) {
                timetableSection.classList.remove('timetable-disabled');
                // Re-enable interactive elements if needed (though pointer-events: none handles most)
                addPeriodBtn.disabled = false;
                newNameInput.disabled = false;
                newStartTimeInput.disabled = false;
                newEndTimeInput.disabled = false;
                document.querySelectorAll('.day-button').forEach(btn => btn.disabled = false);
            } else {
                timetableSection.classList.add('timetable-disabled');
                // Disable interactive elements
                addPeriodBtn.disabled = true;
                newNameInput.disabled = true;
                newStartTimeInput.disabled = true;
                newEndTimeInput.disabled = true;
                document.querySelectorAll('.day-button').forEach(btn => btn.disabled = true);
            }
        };

        /**
         * Applies the visual state of the manual bell controls based on isHardSwitchEnabled.
         */
        const applyHardSwitchState = () => {
           return
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
                // Ensure time values are in HH:MM:SS format for display and input fields using the new helper
                const displayStartTime = formatTimeForInput(period.start_time);
                const displayEndTime = formatTimeForInput(period.end_time);

                const row = document.createElement('tr');
                row.id = `period-${period.id}`;
                row.className = `transition duration-150 ease-in-out ${period.active ? 'hover:bg-gray-50' : 'deactivated-row'}`;
                row.dataset.editing = 'false';

                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap" data-label="Period Name">
                        <span class="period-name text-gray-900 font-medium">${period.name}</span>
                        <input type="text" value="${period.name}" class="edit-input period-name-input hidden" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-label="Day">
                        <span class="period-day text-gray-700">${period.day_of_week}</span>
                        <select class="edit-input period-day-input hidden">
                            ${daysOfWeek.filter(d => d !== 'All Days').map(day => `<option value="${day}" ${period.day_of_week === day ? 'selected' : ''}>${day}</option>`).join('')}
                        </select>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-label="Start Time">
                        <span class="period-start-time text-gray-700">${displayStartTime}</span>
                        <input type="text" value="${displayStartTime}" class="edit-input period-start-time-input hidden" placeholder="HH:MM:SS" pattern="^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-label="End Time">
                        <span class="period-end-time text-gray-700">${displayEndTime}</span>
                        <input type="text" value="${displayEndTime}" class="edit-input period-end-time-input hidden" placeholder="HH:MM:SS" pattern="^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Active">
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
                    <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Actions">
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
            const selects = rowElement.querySelectorAll('select.edit-input');
            const editBtn = rowElement.querySelector('.edit-btn');
            const saveBtn = rowElement.querySelector('.save-btn');

            if (enable) {
                spans.forEach(span => span.classList.add('hidden'));
                inputs.forEach(input => input.classList.remove('hidden'));
                selects.forEach(select => select.classList.remove('hidden'));
                editBtn.classList.add('hidden');
                saveBtn.classList.remove('hidden');
                rowElement.dataset.editing = 'true';
            } else {
                spans.forEach(span => span.classList.remove('hidden'));
                inputs.forEach(input => input.classList.add('hidden'));
                selects.forEach(select => select.classList.add('hidden'));
                editBtn.classList.remove('hidden');
                saveBtn.classList.add('hidden');
                rowElement.dataset.editing = 'false';
            }
        };

        /**
         * Fetches periods from the backend.
         * @returns {Promise<void>}
         */
        const fetchPeriods = async () => {
            // Only fetch if timetable is enabled
            if (!isTimetableEnabled) {
                timetableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="loading-message">Timetable is currently turned off.</td>
                    </tr>
                `;
                loadingMessage.classList.add('hidden');
                return;
            }

            loadingMessage.classList.remove('hidden');
            try {
                const url = `${PHP_API_URL}?action=get_all&day=${encodeURIComponent(currentDay)}`;
         // Log the URL being fetched

                const response = await fetch(url);

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('HTTP Error Response:', response.status, response.statusText, errorText);
                    throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText.substring(0, 200)}...`);
                }

                const data = await response.json();
// Log the periods array specifically

                if (data.success) {
                    data.periods.sort((a, b) => {
                        // Sort by start_time as per backend's field name
                        if (a.start_time < b.start_time) return -1;
                        if (a.start_time > b.start_time) return 1;
                        return 0;
                    });
                    renderTimetable(data.periods);
                } else {
                    renderTimetable([]); // Clear table on error
                    await showCustomModal("Backend Error", data.message);
                }
            } catch (error) {
                console.error('Error fetching periods:', error);
                renderTimetable([]);
                await showCustomModal("Network Error", `Could not fetch timetable. Details: ${error.message || error}. Please check your network connection and backend URL.`);
            } finally {
                loadingMessage.classList.add('hidden');
            }
        };

        /**
         * Adds a new period to the backend.
         * @param {object} periodData - The data for the new period.
         * @returns {Promise<void>}
         */
        const addPeriod = async (periodData) => {
            try {
                // Include 'action' in the JSON body for add
                const response = await fetch(PHP_API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'add', ...periodData }),
                });
                const data = await response.json();

                if (data.success) {
                    await showCustomModal("Success", data.message);
                    await fetchPeriods();
                } else {
                    await showCustomModal("Add Error", data.message);
                }
            } catch (error) {
                console.error('Error adding period:', error);
                await showCustomModal("Network Error", "Could not add period. Please check your network connection.");
            }
        };

        /**
         * Updates an existing period in the backend.
         * @param {string} id - The ID of the period to update.
         * @param {object} updatedData - The updated data for the period.
         * @returns {Promise<void>}
         */
        const updatePeriod = async (id, updatedData) => {
            try {
                // Include 'action' in the JSON body for update
                const response = await fetch(PHP_API_URL, { // URL without query string
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'update', id: id, ...updatedData }), // Pass action and ID in body
                });
                const data = await response.json();

                if (data.success) {
                    await showCustomModal("Success", data.message);
                    await fetchPeriods();
                } else {
                    await showCustomModal("Update Error", data.message);
                }
            } catch (error) {
                console.error('Error updating period:', error);
                await showCustomModal("Network Error", "Could not update period. Please check your network connection.");
            }
        };

        /**
         * Deletes a period from the backend.
         * @param {string} id - The ID of the period to delete.
         * @returns {Promise<void>}
         */
        const deletePeriod = async (id) => {
            try {
                // Include 'action' and 'id' in the JSON body for delete
                const response = await fetch(PHP_API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'delete', id: id }),
                });
                const data = await response.json();

                if (data.success) {
                    await showCustomModal("Success", data.message);
                    await fetchPeriods();
                } else {
                    await showCustomModal("Delete Error", data.message);
                }
            } catch (error) {
                console.error('Error deleting period:', error);
                await showCustomModal("Network Error", "Could not delete period. Please check your network connection.");
            }
        };

        // Event listener attachment function
        const attachEventListeners = () => {
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.onclick = (e) => {
                    const row = e.target.closest('tr');
                    toggleEditMode(row, true);
                };
            });

            document.querySelectorAll('.save-btn').forEach(button => {
                button.onclick = async (e) => {
                    const id = e.target.closest('button').dataset.id;
                    const row = e.target.closest('tr');
                    const name = row.querySelector('.period-name-input').value.trim(); // Trim whitespace
                    const day_of_week = row.querySelector('.period-day-input').value; // Changed to day_of_week
                    const start_time = row.querySelector('.period-start-time-input').value.trim(); // Changed to start_time
                    const end_time = row.querySelector('.period-end-time-input').value.trim();     // Changed to end_time

                    // Client-side validation for HH:MM:SS format
                    const timeRegex = /^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/;
                    if (!timeRegex.test(start_time) || !timeRegex.test(end_time)) {
                        await showCustomModal("Input Error", "Invalid time format. Please use HH:MM:SS (e.g., 09:00:00).");
                        return;
                    }

                    // Client-side validation: End time must not be before start time
                    if (start_time >= end_time) {
                        await showCustomModal("Input Error", "End Time must be after Start Time.");
                        return;
                    }

                    // Pass updated data with correct keys to backend
                    await updatePeriod(id, { name, day_of_week, start_time, end_time });
                };
            });

            document.querySelectorAll('.delete-btn').forEach(button => {
                button.onclick = async (e) => {
                    const id = e.target.closest('button').dataset.id;
                    const confirmed = await showCustomModal("Delete Period", "Are you sure you want to delete this period?");
                    if (confirmed) {
                        await deletePeriod(id);
                    }
                };
            });

            // MODIFICATION START: Refine toggle-switch-checkbox listener
            document.querySelectorAll('.toggle-switch-checkbox').forEach(checkbox => {
                // Only attach this listener if the checkbox has a data-id (i.e., it's a period toggle)
                if (checkbox.dataset.id) {
                    checkbox.onchange = async (e) => {
                        const id = e.target.dataset.id;
                        const newActiveStatus = e.target.checked;
                        await updatePeriod(id, { active: newActiveStatus });
                    };
                }
                // Global toggles (toggleTimetableVisibility, toggleHardSwitch) will have their own specific onchange handlers below.
            });
            // MODIFICATION END
        };

        // Add New Period button click handler
        addPeriodBtn.onclick = async () => {
            const name = newNameInput.value.trim();
            const start_time = newStartTimeInput.value.trim(); // Changed to start_time
            const end_time = newEndTimeInput.value.trim();     // Changed to end_time
            
            const day_of_week = currentDay; // Changed to day_of_week

            if (!name || !start_time || !end_time) {
                await showCustomModal("Input Error", "Please fill in all fields for the new period.");
                return;
            }

            if (day_of_week === 'All Days') {
                await showCustomModal("Selection Required", "Please select a specific day (e.g., Monday) from the top menu before adding a new period.");
                return;
            }

            // Client-side validation for HH:MM:SS format for new period
            const timeRegex = /^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/;
            if (!timeRegex.test(start_time) || !timeRegex.test(end_time)) {
                await showCustomModal("Input Error", "Invalid time format. Please use HH:MM:SS (e.g., 09:00:00).");
                return;
            }

            // Client-side validation: End time must not be before start time for new period
            if (start_time >= end_time) {
                await showCustomModal("Input Error", "End Time must be after Start Time.");
                return;
            }

            await addPeriod({
                name: name,
                day_of_week: day_of_week, // Changed to day_of_week
                start_time: start_time,   // Changed to start_time
                end_time: end_time,     // Changed to end_time
                active: true,
            });
            newNameInput.value = '';
            newStartTimeInput.value = '';
            newEndTimeInput.value = '';
        };

        // Function to render day selection buttons
        const renderDaySelectionButtons = () => {
            daySelectionContainer.innerHTML = '';
            daysOfWeek.forEach(day => {
                const button = document.createElement('button');
                button.textContent = day;
                button.className = `day-button ${currentDay === day ? 'active' : ''}`;
                button.dataset.day = day;
                button.onclick = () => {
                    currentDay = day;
                    document.querySelectorAll('.day-button').forEach(btn => {
                        if (btn.dataset.day === currentDay) {
                            btn.classList.add('active');
                        } else {
                            btn.classList.remove('active');
                        }
                    });
                    fetchPeriods();
                };
                daySelectionContainer.appendChild(button);
            });
        };

        // Function to switch sections
        const showSection = (sectionId) => {
            timetableSection.classList.remove('active-section');
            timetableSection.classList.add('hidden-section');
            manualSection.classList.remove('active-section');
            manualSection.classList.add('hidden-section');
            settingsSection.classList.remove('active-section');
            settingsSection.classList.add('hidden-section');

            navTimetableBtn.classList.remove('active');
            navManualBtn.classList.remove('active');
            navSettingsBtn.classList.remove('active');

            if (sectionId === 'timetableSection') {
                timetableSection.classList.remove('hidden-section');
                timetableSection.classList.add('active-section');
                navTimetableBtn.classList.add('active');
                fetchPeriods(); // Re-fetch timetable when switching back
            } else if (sectionId === 'manualSection') {
                manualSection.classList.remove('hidden-section');
                manualSection.classList.add('active-section');
                navManualBtn.classList.add('active');
                // When navigating to manual, ensure hard switch state is applied
                applyHardSwitchState();
            } else if (sectionId === 'settingsSection') {
                settingsSection.classList.remove('hidden-section');
                settingsSection.classList.add('active-section');
                navSettingsBtn.classList.add('active');
                // When navigating to settings, ensure email and settings are updated
                fetchUserEmailAndSettings(); 
            }
            applyTimetableVisibility(); // Apply visibility state whenever section changes
        };

        // Add event listeners to navigation buttons
        navTimetableBtn.onclick = () => showSection('timetableSection');
        navManualBtn.onclick = () => showSection('manualSection');
        navSettingsBtn.onclick = () => showSection('settingsSection');


        // --- Settings Section Functions ---
        const currentPasswordInput = document.getElementById('currentPassword');
        const newPasswordInput = document.getElementById('newPassword');
        const confirmNewPasswordInput = document.getElementById('confirmNewPassword');
        const changePasswordBtn = document.getElementById('changePasswordBtn');

        const emailCurrentPasswordInput = document.getElementById('emailCurrentPassword');
        const newEmailInput = document.getElementById('newEmail');
        const changeEmailBtn = document.getElementById('changeEmailBtn');

        const logoutBtn = document.getElementById('logoutBtn');

        changePasswordBtn.onclick = async () => {
            const currentPassword = currentPasswordInput.value.trim();
            const newPassword = newPasswordInput.value.trim();
            const confirmNewPassword = confirmNewPasswordInput.value.trim();

            if (!currentPassword || !newPassword || !confirmNewPassword) {
                await showCustomModal("Input Error", "Please fill in all password fields.");
                return;
            }
            if (newPassword !== confirmNewPassword) {
                await showCustomModal("Input Error", "New password and confirmation do not match.");
                return;
            }
            if (newPassword.length < 8) { // Basic password strength
                await showCustomModal("Input Error", "New password must be at least 8 characters long.");
                return;
            }

            try {
                // Include 'action' in the JSON body for change_password
                const payload = { action: 'change_password', current_password: currentPassword, new_password: newPassword };
            
                const response = await fetch(PHP_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                await showCustomModal(data.success ? "Success" : "Error", data.message);
                if (data.success) {
                    currentPasswordInput.value = '';
                    newPasswordInput.value = '';
                    confirmNewPasswordInput.value = '';
                }
            } catch (error) {
                console.error('Error changing password:', error);
                await showCustomModal("Network Error", "Could not change password. Please check your network connection.");
            }
        };

        changeEmailBtn.onclick = async () => {
            const currentPassword = emailCurrentPasswordInput.value.trim();
            const newEmail = newEmailInput.value.trim();

            if (!currentPassword || !newEmail) {
                await showCustomModal("Input Error", "Please fill in current password and new email.");
                return;
            }
            // Basic email format validation
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
                await showCustomModal("Input Error", "Please enter a valid email address.");
                return;
            }

            try {
                // Include 'action' in the JSON body for change_email
                const payload = { action: 'change_email', current_password: currentPassword, new_email: newEmail };
           
                const response = await fetch(PHP_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                await showCustomModal(data.success ? "Success" : "Error", data.message);
                if (data.success) {
                    newEmailInput.value = '';
                    emailCurrentPasswordInput.value = '';
                    fetchUserEmailAndSettings(); // Update displayed email and settings
                }
            } catch (error) {
                console.error('Error changing email:', error);
                await showCustomModal("Network Error", "Could not change email. Please check your network connection.");
            }
        };

        logoutBtn.onclick = async () => {
            const confirmed = await showCustomModal("Logout", "Are you sure you want to log out?");
            if (confirmed) {
                try {
                    // Send the action in the JSON body for POST requests
                    const payload = { action: 'logout' };
                   
                    const response = await fetch(PHP_API_URL, { // URL without query string
                        method: 'POST', 
                        headers: { 
                            'Content-Type': 'application/json' 
                        },
                        body: JSON.stringify(payload) // Action sent in JSON body
                    });

                    // Check if the response is OK before trying to parse JSON
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('HTTP Error during logout:', response.status, response.statusText, errorText);
                        // You might want a more specific error display here, e.g., using displayError if available
                        await showCustomModal("Logout Error", `Server responded with status ${response.status}: ${errorText.substring(0, 100)}...`);
                        return; // Stop execution if response is not ok
                    }

                    const data = await response.json(); // Parse JSON response
                  

                    if (data.success) {
                        await showCustomModal("Success", data.message);
                        // Redirect after successful logout
                        window.location.href = '../index.html'; // Assuming this is your login page
                    } else {
                        await showCustomModal("Error", data.message);
                    }
                } catch (error) {
                    console.error('Error logging out:', error);
                    await showCustomModal("Network Error", "Could not log out. Please check your network connection or server status.");
                }
            }
        };

        // Event listener for the new timetable visibility toggle (specific handler)
        toggleTimetableVisibility.onchange = async (e) => {
            const enabled = e.target.checked;
            try {
                // Include 'action' in the JSON body for toggle_timetable_visibility
                const payload = { action: 'toggle_timetable_visibility', enabled: enabled };
               
                const response = await fetch(PHP_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    isTimetableEnabled = enabled; // Update global state
                    applyTimetableVisibility(); // Apply visual changes
                    // If we are on the timetable section, re-fetch to show the "disabled" message
                    if (timetableSection.classList.contains('active-section')) {
                        fetchPeriods();
                    }
                    await showCustomModal("Success", data.message);
                } else {
                    // Revert toggle state if backend update failed
                    toggleTimetableVisibility.checked = !enabled;
                    await showCustomModal("Error", data.message);
                }
            } catch (error) {
                console.error('Error toggling timetable visibility:', error);
                toggleTimetableVisibility.checked = !enabled; // Revert on network error
                await showCustomModal("Network Error", "Could not toggle timetable visibility. Please check your network connection.");
            }
        };

        // --- Manual Section Functions ---
        ringBellBtn.onclick = async () => {
            if (!isHardSwitchEnabled) {
                await showCustomModal("Bell Deactivated", "The bell cannot be rung manually because the hard switch is OFF.");
                return;
            }

            try {
                // No Tone.js specific code here anymore. The bell sound functionality
                // would need to be implemented separately if desired, without Tone.js.

                // Include 'action' in the JSON body for ring_bell
                const payload = { action: 'ring_bell' };
              
                const response = await fetch(PHP_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    // Send the message "on" through the WebSocket
                     await sendMessage("AUTO_ON");
                     await showCustomModal(data.success ? "Bell Ring" : "Error", data.message);
                   }
                
            } catch (error) {
                console.error('Error ringing bell:', error);
                await showCustomModal("Network Error", "Could not ring bell. Please check your network connection.");
            }
        };

        // Event listener for the hard switch toggle (specific handler)
        toggleHardSwitch.onchange = async (e) => {
            const enabled = e.target.checked;
            try {
                // Include 'action' in the JSON body for toggle_hard_switch
                const payload = { action: 'toggle_hard_switch', enabled: enabled };
          
                const response = await fetch(PHP_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    isHardSwitchEnabled = enabled; // Update global state
                    applyHardSwitchState(); // Apply visual change
                    await sendMessage(enabled ? "HARD_ON" : "HARD_OFF"); // Notify server of hard switch state
                    await showCustomModal("Success", data.message);
                } else {
                    // Revert toggle state if backend update failed
                    toggleHardSwitch.checked = !enabled;
                    await showCustomModal("Error", data.message);
                }
            } catch (error) {
                console.error('Error toggling hard switch:', error);
                toggleHardSwitch.checked = !enabled; // Revert on network error
                await showCustomModal("Network Error", "Could not toggle hard switch. Please check your network connection.");
            }
        };
        timezoneSelect.onchange = async (e) => {
    const selectedTimezone = e.target.value;
    try {
        // Construct the payload with the new action and selected timezone
        const payload = { 
            action: 'save_timezone', 
            timezone: selectedTimezone 
        };
       

        // Send the payload to the PHP backend
        const response = await fetch(PHP_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();

        if (data.success) {
            // Display a success message to the user
            await showCustomModal("Success", data.message);
        } else {
            // Display an error and do not revert the UI, as the user's choice is still shown
            await showCustomModal("Error", data.message);
        }
    } catch (error) {
        console.error('Error saving timezone:', error);
        await showCustomModal("Network Error", "Could not save timezone. Please check your network connection.");
    }
    
};
const wsUrl = 'ws://192.168.198.177:4000'; // Change this to your Node.js server address
let ws = null; // WebSocket instance

// --- Core Functions ---

/**
 * Manages the WebSocket connection. This function should be called once on page load.
 */
async function sendMessage(message) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(message);
    } else {
        await showCustomModal('Network Error: ', "Couldn't Connect to the server. Please refresh the page to try again.");
        // Optionally, attempt to reconnect here as well
        connectWebSocket();
    }
}
function connectWebSocket() {
    const Status_indicator=document.getElementById('ServerIndicator'); // Disable button until connection is established   
    // Prevent multiple connections
    if (ws) {
        ws.close();
    }

    ws = new WebSocket(wsUrl);

    ws.onopen = () => {
        Status_indicator.style.color='#00ff00';
        
    };

    // ws.onmessage = async (event) => {
    //      await showCustomModal('Server Message', event.data);

    // };

    ws.onclose = async (event) => {
        Status_indicator.style.color='#ff0000';
        await showCustomModal('Server Message', event.data);
        
    };

    ws.onerror = async (error) => {
        await showCustomModal('Network Error: ', error.message || error);
    };
}

/**
 * Sends a message via the WebSocket.
 * @param {string} message The message to send.
 */
async function sendMessage(message) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(message);
    } else {
        await showCustomModal('Cannot send message. Not connected.');
        // Optionally, attempt to reconnect here as well
        connectWebSocket();
    }
}



// --- Main Event Handlers ---

document.addEventListener('DOMContentLoaded', () => {
    // Automatically connect to the WebSocket when the page is fully loaded
    connectWebSocket();
})       

        // Initial setup when the window loads
        window.onload = async () => {
            connectWebSocket();
            await fetchTimezone();
            await fetchUserEmailAndSettings(); // Fetch and display user email and settings (including hard switch)
            renderDaySelectionButtons(); // Render day buttons
            showSection('timetableSection'); // Show timetable section by default (will apply visibility based on fetched setting)
        };
  