const LoginForm = document.getElementById('LoginForm')?.querySelector('form');
const signupForm = document.getElementById('signUpForm')?.querySelector('form');
const LoginErrorText = document.querySelector('#LoginForm .MsgBox');
const signupErrorText = document.querySelector('#signUpForm .MsgBox');

// IMPORTANT: Ensure this URL exactly matches your PHP backend' s URL.
// Based on your HTML, it's 'http://localhost/timetable_app/timetable_api.php'
const PHP_API_URL = 'http://localhost:3000/backend/main.php';



function displaySuccess(element, message) {
    if (element) {
        element.style.display = 'block';
        element.classList.remove('error');
        element.classList.add('success');
        element.textContent = message;
        setTimeout(() => {
            element.style.animation = 'fade_in 2s 1s 1 reverse';
            element.addEventListener('animationend', () => {
                clearError(element);
            }, { once: true });
        }, 3000);
    }
}

function displayError(element, message) {
    if (element) {
        element.classList.remove('success');
        element.classList.add('error');
        element.style.display = 'block';
        element.textContent = message;
        setTimeout(() => {
            element.style.animation = 'fade_in 2s 1s 1 reverse';
            element.addEventListener('animationend', () => {
                clearError(element);
            }, { once: true });
        }, 3000);
    }
}

function clearError(element) {
    if (element) {
        element.style.display = 'none';
        element.textContent = '';
        element.classList.remove('success', 'error');
    }
}

LoginForm?.addEventListener('submit', function(event) {
    event.preventDefault();
    // Pass 'login' as the action, and use the correct PHP_API_URL
    handleSubmit('login', this, PHP_API_URL, LoginErrorText, 'Login successful', 'webSection/page.html', () => this.reset());
});

signupForm?.addEventListener('submit', function(event) {
    event.preventDefault();
    
    const passwordInput = this.querySelector('[name="password"]');
    const confirmPasswordInput = this.querySelector('[name="confirm_password"]');

    if (passwordInput && confirmPasswordInput && passwordInput.value !== confirmPasswordInput.value) {
        displayError(signupErrorText, 'Passwords do not match!');
        return;
    } else {
        clearError(signupErrorText);
    }
    // Pass 'register' as the action, and use the correct PHP_API_URL
    handleSubmit('register', this, PHP_API_URL, signupErrorText, 'Account created successfully!', null, () => this.reset());
});

async function handleSubmit(action, form, url, errorElement, successMessage, redirectUrl = null, successCallback = null) {
    try {
      const formData = new FormData(form);
      formData.append("action", action); // Changed 'mode' to 'action' to match backend expectation

      const response = await fetch(url, {
        method: "POST",
        body: formData, // This sends as multipart/form-data, which PHP's $_POST handles
      });

      if (!response.ok) {
        const errorMessage = await response.text();
        throw new Error(
          `HTTP error! status: ${response.status}, message: ${errorMessage}`
        );
      }
      // const data = await response.json(); // Parse JSON response
      // console.log('Server response:', data);
        const rawText = await response.text(); // Get raw response as plain text console.log("Raw server response:", rawText);
        console.log(rawText)
    const data = JSON.parse(rawText);

      if (data.success) {
        displaySuccess(errorElement, data.message);
        if (successCallback && typeof successCallback === "function") {
          const callbackResult = successCallback(data);
          // Only redirect if callbackResult is not explicitly false
          if (redirectUrl && callbackResult !== false) {
            if (data.role == "Admin") {
              window.location.href = redirectUrl;
            } else {
              window.location.href = "Admin/index.html";
            }
          }
        } else if (redirectUrl) {
          // If no specific callback, just redirect
          if (data.role == "Admin") {
            window.location.href = redirectUrl;
          } else {
            window.location.href = "Admin/index.html";
          }
        }
      } else {
        displayError(errorElement, data.message); // Display server's error message
        if (successCallback && typeof successCallback === "function") {
          successCallback(data); // Optional custom error handling
        }
      }
    } catch (error) {
        console.error('Submission failed:', error);
        displayError(errorElement, `Network or server error: ${error.message}. Please try again.`);
    }
}