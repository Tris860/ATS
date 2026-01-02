// ✅ Config
const PHP_API_URL = "http://localhost:3000/backend/main.php";
const statusMap = {
  1: "Active",
  0: "Trial",
  2: "Expiring",
  3: "Overdue",
};

// ✅ Global state
let users = [];
let filter = "All";
let searchQuery = "";
let activeId = null;

// ✅ Set filter and fetch users
async function setFilter(t) {
  filter = t;
  renderFilters();
  const statusCodes = { Active: 1,  Expired: 0};
  const statusCode = statusCodes[t];
  await fetchUsersByStatus(t === "All" ? "All" : statusCode);
}


// ✅ Render filter buttons
function renderFilters() {
  const types = ["All", "Active", "Expired"];
  document.getElementById("filter-bar").innerHTML = types
    .map(
      (t) => `
        <button data-filter="${t}" class="filter-btn ${
        filter === t ? "active" : ""
      }">${t}</button>
      `
    )
    .join("");
}

// ✅ Render stats dynamically from backend
async function renderStats() {
  const categories = [
    {
      label: "Total",
      endpoint: "get_all_users",
      icon: "fa-users",
      color: "#006affff",
    },
    {
      label: "Active",
      endpoint: "get_users_by_status&status=1",
      icon: "fa-check",
      color: "#16a34a",
    },
    {
      label: "Expired",
      endpoint: "get_users_by_status&status=0",
      icon: "fa-flask",
      color: "#dc2626",
    },
    // { label: "Risk", endpoint: "get_users_by_status&status=2", icon: "fa-bolt", color: "#ea580c" },
    // { label: "Lapsed", endpoint: "get_users_by_status&status=3", icon: "fa-xmark", color: "#dc2626" },
  ];

  const stats = [];

  for (const cat of categories) {
    try {
      const res = await fetch(`${PHP_API_URL}?action=${cat.endpoint}`);
      const raw = await res.text();
      const data = JSON.parse(raw);
      stats.push({ ...cat, val: data.success ? data.data.length : 0 });
    } catch (err) {
      console.error(`Error fetching ${cat.label} stats:`, err);
      stats.push({ ...cat, val: 0 });
    }
  }

  document.getElementById("stats-grid").innerHTML = stats
    .map(
      (s) => `
        <div class="stat-card" style="border-left: 4px solid ${s.color}">
          <i class="fa-solid ${s.icon} stat-icon" style="color:${s.color}"></i>
          <h3>${s.val}</h3>
          <p>${s.label}</p>
        </div>
      `
    )
    .join("");
}

// ✅ Fetch users by status
async function fetchUsersByStatus(statusFilter = "All") {
  try {
    const url =
      statusFilter === "All"
        ? `${PHP_API_URL}?action=get_all_users`
        : `${PHP_API_URL}?action=get_users_by_status&status=${encodeURIComponent(statusFilter)}`;

    const res = await fetch(url);
    const raw = await res.text();
    const data = JSON.parse(raw);
  
      if (data.success) {
          console.log(data)
        users = data.data.map((u) => ({
          id: String(u.id || u.subscription_id), // always pick one consistently
          email: u.email,
          status: statusMap[u.status] || "Unknown",
          plan: u.plan || "N/A",
          expiry: u.date_of_expiry || "N/A",
        }));

      renderList();
    } else {
      showCustomModal("User Fetch Error", data.message);
    }
  } catch (err) {
    console.error("Network error fetching users:", err);
    showCustomModal("Network Error", "Could not fetch user list.");
  }
}

// ✅ Render user list
function renderList() {
  const filtered = users.filter((u) => {
    const matchesSearch = u.email.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesFilter = filter === "All" || u.status === filter;
    return matchesSearch && matchesFilter;
  });

  document.getElementById("user-list").innerHTML = filtered
    .map(
      (u) => `
        <tr>
          <td data-label="User"><div class="text-bold">${u.email}</div></td>
          <td data-label="Status"><span class="status-pill status-${u.status}">${u.status}</span></td>
          <td data-label="Plan"><div class="text-semibold">${u.plan}</div></td>
          <td data-label="Expiry"><div class="text-small">${u.expiry}</div></td>
          <td class="actions-cell">
            <div class="actions">
              <button class="btn-icon" data-action="pwd" data-user-id="${u.id}">
                <i class="fa-solid fa-key"></i>
              </button>
              <button class="btn-renew" data-action="renew" data-user-id="${u.id}">Renew</button>
            </div>
          </td>
        </tr>
      `
    )
    .join("");
}

// ✅ Toast notification
function notify(msg) {
  const container = document.getElementById("toast-container");
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.innerHTML = `<i class="fa-solid fa-circle-check toast-icon"></i> <span>${msg}</span>`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// ✅ Modal helpers
function openRenewModal(id) {
  activeId = id;
  const u = users.find((x) => x.id === id);
  document.getElementById("renew-target").innerText = `Extend subscription for ${u.email}`;
  document.getElementById("modal-backdrop").style.display = "flex";
  document.getElementById("renew-modal").style.display = "block";
}

function openPwdModal(id) {
  activeId = id;
  document.getElementById("modal-backdrop").style.display = "flex";
  document.getElementById("pwd-modal").style.display = "block";
}

function closeModal() {
  document.getElementById("modal-backdrop").style.display = "none";
  document.getElementById("renew-modal").style.display = "none";
  document.getElementById("pwd-modal").style.display = "none";
}

// ✅ Process renewal
async function processRenewal(planType) {
  try {
    const res = await fetch(PHP_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `action=update_subscription&plan_type=${encodeURIComponent(
        planType
      )}&user_id=${encodeURIComponent(activeId)}`,
    });

    const raw = await res.text();
    const data = JSON.parse(raw);

    if (data.success) {
      notify(`Subscription renewed (${planType})`);
      closeModal();
      await renderStats();
      await setFilter(filter);
    } else {
      showCustomModal("Renewal Error", data.message);
    }
  } catch (err) {
    showCustomModal("Network Error", "Could not process renewal.");
  }
}


// ✅ Process password update
async function processPwd() {
  const newPassword = document.getElementById("new-pwd").value;
  if (!newPassword) {
    notify("Password cannot be empty");
    return;
  }

  try {
    const res = await fetch(PHP_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `action=update_password&new_password=${encodeURIComponent(
        newPassword
      )}&user_id=${encodeURIComponent(activeId)}`,
    });

    const raw = await res.text();
    const data = JSON.parse(raw);

    if (data.success) {
      notify("Password updated");
      closeModal();
    } else {
      showCustomModal("Password Update Error", data.message);
    }
  } catch (err) {
    showCustomModal("Network Error", "Could not update password.");
  }
}


// ✅ Modal fallback
function showCustomModal(title, message) {
  alert(`${title}: ${message}`);
}

// ✅ Event wiring
function setupEventListeners() {
  // Search
  document.getElementById("search").addEventListener("input", async (e) => {
    searchQuery = e.target.value;
    await setFilter(filter);
  });

  // Filter buttons (delegated)
  document.getElementById("filter-bar").addEventListener("click", async (e) => {
    const btn = e.target.closest("button[data-filter]");
    if (!btn) return;
    filter = btn.dataset.filter;
    renderFilters();
    const statusMap = { Active: 1, Trial: 0, Expiring: 2, Overdue: 3 };
    const statusCode = statusMap[filter];
    await fetchUsersByStatus(filter === "All" ? "All" : statusCode);
  });

  // User list actions (delegated)
  // User list actions (delegated)
  document.getElementById("user-list").addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;
    const id = btn.dataset.userId;
    if (btn.dataset.action === "renew") openRenewModal(id);
    if (btn.dataset.action === "pwd") openPwdModal(id);
  });

  // Renew modal buttons
  document
    .getElementById("renew-trial")
    .addEventListener("click", () => processRenewal("free trial"));
  document
    .getElementById("renew-standard")
    .addEventListener("click", () => processRenewal("standard"));
  document.getElementById("cancel-renew").addEventListener("click", closeModal);

  // Password modal buttons
  document.getElementById("update-pwd").addEventListener("click", processPwd);
  document.getElementById("cancel-pwd").addEventListener("click", closeModal);

  // Backdrop click closes modal
  document.getElementById("modal-backdrop").addEventListener("click", (e) => {
    if (e.target === e.currentTarget) closeModal();
  });
}
async function logout() {
  try {
    const res = await fetch(PHP_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "action=logout",
    });
    const raw = await res.text();
    const data = JSON.parse(raw);

    if (data.success) {
      notify("You have been logged out.");
      // Optionally redirect to login page
      window.location.href = "../index.html";
    } else {
      showCustomModal("Logout Error", data.message);
    }
  } catch (err) {
    showCustomModal("Network Error", "Could not log out.");
  }
}

document.getElementById("logout").addEventListener("click", async () => {
    await logout();
});
// ✅ Initial load

async function init() {
  renderFilters();
  setupEventListeners();
  await renderStats();
  await setFilter("All");
};
init();