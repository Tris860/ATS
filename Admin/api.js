const PHP_API_URL = "http://localhost:3000/backend/main.php";

const handleResponse = async (response) => {
  if (!response.ok) {
      const errorText = await response.text();
      console.log("Error Response Text:", errorText);
    throw new Error(
      `HTTP error! Status: ${response.status}, Details: ${errorText.substring(0, 200)}`,
    );
  }
  const data = await response.json();
    if (!data.success) {
      console.log("API Error Response:", data);
    throw new Error(data.message || "Backend operation failed");
  }
  return data;
};

/**
 * POST: Create/Send new data
 */
export const postData = async (action, payload) => {
  try {
    const response = await fetch(PHP_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    return await handleResponse(response);
  } catch (error) {
    console.error(`Error in postData (${action}):`, error);
    throw error;
  }
};

/**
 * FETCH: Get specific data
 */
export const fetchData = async (action, params = {}) => {
  try {
    const queryString = new URLSearchParams(params).toString();
    const url = `${PHP_API_URL}?action=${action}${queryString ? `&${queryString}` : ""}`;

    const response = await fetch(url);
    return await handleResponse(response);
  } catch (error) {
    console.error(`Error in fetchData (${action}):`, error);
    throw error;
  }
};

/**
 * UPDATE: Modify existing data
 */
export const updateData = async (action, payload) => {
  try {
    const response = await fetch(`${PHP_API_URL}?action=${action}`, {
      method: "POST", // Or POST depending on your PHP backend configuration
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    return await handleResponse(response);
  } catch (error) {
    console.error(`Error in updateData (${action}):`, error);
    throw error;
  }
};

/**
 * DELETE: Remove data
 */
export const deleteData = async (action, payload) => {
    console.log("action in deleteData:", action);
  try {
    const response = await fetch(`${PHP_API_URL}?action=${action}`, {
      method: "POST", // Or DELETE depending on your PHP backend configuration
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    return await handleResponse(response);
  } catch (error) {
    console.error(`Error in deleteData (${action}):`, error);
    throw error;
  }
};

class SuperAdminMonitor {
  constructor(secretKey) {
    this.url = `wss://combined-server-1fyr.onrender.com/ws/admin?secret_key=${secretKey}`;
    this.socket = null;
    this.onGlobalUpdate = null;
  }

  init() {
    this.socket = new WebSocket(this.url);

    this.socket.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.type === "global_stats") {
        if (this.onGlobalUpdate) {
          this.onGlobalUpdate({
            totalOnline: data.online_count,
            deviceList: data.devices,
            timestamp: new Date().toLocaleTimeString(),
          });
        }
      }
    };

    this.socket.onclose = () => {
      console.warn("Admin socket closed. Reconnecting...");
      setTimeout(() => this.init(), 3000);
    };

    this.socket.onerror = (err) => console.error("Admin Auth Error:", err);
  }
}

// Example Implementation
export const admin = new SuperAdminMonitor("YOUR_SUPER_ADMIN_SECRET");

