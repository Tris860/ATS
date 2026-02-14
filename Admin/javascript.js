// ✅ Config
const PHP_API_URL = "http://localhost:3000/backend/main.php";

import { postData, fetchData, updateData, deleteData,admin } from "./api.js";

const statusMap = {
  1: "Active",
  0: "Trial",
  2: "Expiring",
  3: "Overdue",
};

// ✅ Global state
let users = [];
let boards = [];
let boardsStatus = [];
let plans = [];
let filter = "All";
let searchQuery = "";
let activeId = null;
let activeBoardId = null;
let activePlanId = null;
let currentPage = "dashboard";
let isEditMode = false;

// ✅ Charts
let userGrowthChart = null;
let subscriptionChart = null;
let revenueChart = null;
let boardStatusChart = null;
let retentionChart = null;

// ============================================
// NAVIGATION
// ============================================

function setupNavigation() {
  const navItems = document.querySelectorAll(".nav-item");
  const mobileToggle = document.getElementById("mobile-toggle");
  const sidebar = document.getElementById("sidebar");

  navItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      e.preventDefault();
      const page = item.dataset.page;
      showPage(page);

      // Update active nav item
      navItems.forEach((nav) => nav.classList.remove("active"));
      item.classList.add("active");

      // Close sidebar on mobile
      if (window.innerWidth <= 1024) {
        sidebar.classList.remove("active");
      }
    });
  });

  // Mobile toggle
  if (mobileToggle) {
    mobileToggle.addEventListener("click", () => {
      sidebar.classList.toggle("active");
    });
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", (e) => {
    if (
      window.innerWidth <= 1024 &&
      !sidebar.contains(e.target) &&
      !mobileToggle.contains(e.target)
    ) {
      sidebar.classList.remove("active");
    }
  });
}

function showPage(pageName) {
  currentPage = pageName;

  // Hide all pages
  document.querySelectorAll(".page").forEach((page) => {
    page.classList.remove("active");
  });

  // Show selected page
  const page = document.getElementById(`page-${pageName}`);
  if (page) {
    page.classList.add("active");
  }

  // Update page title
  const titles = {
    dashboard: "Dashboard Overview",
    users: "User Management",
    boards: "Board Management",
    plans: "Subscription Plans",
    analytics: "Analytics & Reports",
    settings: "Settings",
  };

  document.getElementById("page-title").textContent =
    titles[pageName] || "Dashboard";

  // Load page-specific data
  switch (pageName) {
    case "dashboard":
      renderDashboard();
      break;
    case "users":
      setFilter("All");
      break;
    case "boards":
      fetchBoards();
      break;
    case "plans":
      fetchPlans();
      break;
    case "analytics":
      renderAnalytics();
      break;
  }
}

// ============================================
// DASHBOARD
// ============================================

async function renderDashboard() {
  await renderStats();
  await renderCharts();
  renderRecentActivity();
}

async function renderStats() {
  const categories = [
    {
      label: "Total Users",
      endpoint: "get_all_users",
      icon: "fa-users",
      color: "#006affff",
    },
   /*  {
      label: "Active",
      endpoint: "get_users_by_status&status=1",
      icon: "fa-check-circle",
      color: "#16a34a",
    }, */
    /* {
      label: "Expired",
      endpoint: "get_users_by_status&status=0",
      icon: "fa-clock",
      color: "#dc2626",
    }, */
    {
      label: "Total Boards",
      endpoint: "get_all_boards", // You'll need to implement this
      icon: "fa-microchip",
      color: "#ea580c",
    },
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
        <div class="stat-card" style="border-left-color: ${s.color}">
          <i class="fa-solid ${s.icon} stat-icon" style="color:${s.color}"></i>
          <h3>${s.val}</h3>
          <p>${s.label}</p>
        </div>
      `,
    )
    .join("");
}

async function renderCharts() {
  // User Growth Chart
  const userGrowthCtx = document.getElementById("userGrowthChart");
  if (userGrowthCtx && !userGrowthChart) {
    const res = await fetchData("get_admin_new_users_by_month");
    const data = res.success ? res.data : generateMockData(6, 50, 200);

    userGrowthChart = new Chart(userGrowthCtx, {
      type: "line",
      data: {
        labels: data.labels || getLast30Days(),
        datasets: [
          {
            label: "New Users",
            data:data.data || generateMockData(30, 0, 15),
            borderColor: "#fce24e",
            backgroundColor: "rgba(252, 226, 78, 0.1)",
            tension: 0.4,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: "#f1f5f9",
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Subscription Status Chart
  const subscriptionCtx = document.getElementById("subscriptionChart");
  if (subscriptionCtx && !subscriptionChart) {
    const res = await fetchData("get_subscription_status_distribution");
    subscriptionChart = new Chart(subscriptionCtx, {
      type: "doughnut",
      data: {
        labels: res.data.labels || [
          ("Active", "Trial", "Expired", "Unprepared"),
        ],
        datasets: [
          {
            data: res.data.data || [65, 20, 15, 30],
            backgroundColor:
              res.data.backgroundColor ||
              ["#16a34a", "#fce24e", "#dc2626", "#00aeff"],
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
          },
        },
      },
    });
  }
}

function renderRecentActivity() {
  const activities = [
    {
      icon: "fa-user-plus",
      text: "New user registered",
      time: "2 minutes ago",
      color: "#16a34a",
    },
    {
      icon: "fa-rotate",
      text: "Subscription renewed for user@example.com",
      time: "15 minutes ago",
      color: "#fce24e",
    },
    {
      icon: "fa-microchip",
      text: "New board ATS-2024-045 paired",
      time: "1 hour ago",
      color: "#006affff",
    },
    {
      icon: "fa-exclamation-triangle",
      text: "5 subscriptions expiring soon",
      time: "2 hours ago",
      color: "#ea580c",
    },
    {
      icon: "fa-key",
      text: "Password reset for admin@ats.com",
      time: "3 hours ago",
      color: "#64748b",
    },
  ];

  // const container = document.getElementById("recent-activity");
  // if (container) {
  //   container.innerHTML = activities
  //     .map(
  //       (activity) => `
  //     <div class="activity-item">
  //       <div class="activity-icon" style="background: ${activity.color}20;">
  //         <i class="fa-solid ${activity.icon}" style="color: ${activity.color}"></i>
  //       </div>
  //       <div class="activity-content">
  //         <p>${activity.text}</p>
  //         <span>${activity.time}</span>
  //       </div>
  //     </div>
  //   `,
  //     )
  //     .join("");
  // }
}

// ============================================
// USERS MANAGEMENT
// ============================================

async function setFilter(t) {
  filter = t;
  renderFilters();
  const statusCodes = { Active: 1, Expired: 0 };
  const statusCode = statusCodes[t];
  await fetchUsersByStatus(t === "All" ? "All" : statusCode);
}

function renderFilters() {
  const types = ["All", "Active", "Expired"];
  const filterBar = document.getElementById("filter-bar");
  if (filterBar) {
    filterBar.innerHTML = types
      .map(
        (t) => `
          <button data-filter="${t}" class="filter-btn ${
            filter === t ? "active" : ""
          }">${t}</button>
        `,
      )
      .join("");
  }
}

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
      users = data.data.map((u) => ({
        id: String(u.id || u.subscription_id),
        email: u.email,
        status: statusMap[u.status] || "Unknown",
        plan: u.plan || "N/A",
        expiry: u.date_of_expiry || "N/A",
        boardId: u.board_id || "Not paired",
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

async function renderList() {
  const filtered = users.filter((u) => {
    const matchesSearch = u.email
      .toLowerCase()
      .includes(searchQuery.toLowerCase());
    const matchesFilter = filter === "All" || u.status === filter;
    return matchesSearch && matchesFilter;
  });
  const res = await fetchData("get_all_users_admin");

  const allusers = res.data;
  const userList = document.getElementById("user-list");
  if (userList) {
    userList.innerHTML = allusers
      .map(
        (u) => `
          <tr>
            <td data-label="User"><div class="text-bold">${u.email}</div></td>
           
            <td data-label="Plan"><div class="text-semibold">${u.subscription_name}</div></td>
            <td data-label="Board ID"><div class="text-small">${u.hardware_id}</div></td>
            <td data-label="Expiry"><div class="text-small">${u.date_of_expiry}</div></td>
            <td class="actions-cell">
              <div class="actions">
                <button class="btn-icon" data-action="pwd" data-user-id="${u.user_id}" title="Reset Password">
                  <i class="fa-solid fa-key"></i>
                </button>
                <button class="btn-renew" data-action="renew" data-user-id="${u.user_id}">Renew</button>
              </div>
            </td>
          </tr>
        `,
      )
      .join("");
  }
}

// ============================================
// BOARDS MANAGEMENT
// ============================================

async function fetchBoards() {
  // Mock data for boards - replace with actual API call
  boards = [
    {
      id: "ATS-2024-001",
      name: "Main Control Board",
      location: "Building A, Floor 2",
      status: "online",
      paired: true,
      userId: "user@example.com",
    },
    {
      id: "ATS-2024-002",
      name: "Secondary Board",
      location: "Building B, Floor 1",
      status: "online",
      paired: true,
      userId: "test@example.com",
    },
    {
      id: "ATS-2024-003",
      name: "Test Board",
      location: "Lab 3",
      status: "offline",
      paired: false,
      userId: null,
    },
    {
      id: "ATS-2024-004",
      name: "Production Board",
      location: "Factory Floor",
      status: "online",
      paired: true,
      userId: "prod@example.com",
    },
    {
      id: "ATS-2024-005",
      name: "Backup Board",
      location: "Storage Room",
      status: "offline",
      paired: false,
      userId: null,
    },
  ];

  renderBoardsGrid();
}

async function renderBoardsGrid(boards) {
  const grid = document.getElementById("boards-grid");
  if (!grid) return;
  const res = await fetchData("get_all_boards");

  const hardware = res.data;
  boardsStatus[0]= hardware.length ;
  grid.innerHTML = hardware
    .map(
      (board) => `
    <div class="board-card">
      <div class="board-header">
        <div>
          <div class="board-id">${board.id}</div>
          <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.25rem;">${board.name}</p>
        </div>
        <div class="board-status ${board.status}" title="${board.status}"></div>
      </div>
      <div class="board-info">
        <p><i class="fa-solid fa-location-dot" style="color: var(--primary-dark); margin-right: 0.5rem;"></i>${board.location}</p>
        <p><i class="fa-solid fa-link" style="color: var(--primary-dark); margin-right: 0.5rem;"></i>${board.paired ? board.paired_email : "Not paired"}</p>
      </div>
      <div class="board-actions">
        ${
          board.paired
            ? `<button class="btn-secondary" data-action="unpair-board" data-pair-id="${board.paired_user_id}" data-board-id="${board.id}">Unpair</button>`
            : `<button class="btn-primary" data-action="pair-board"  data-board-id="${board.id}">Pair</button>`
        }
        <button class="btn-icon" data-action="edit-board" data-board-id="${board.id}" title="Edit">
          <i class="fa-solid fa-pencil"></i>
        </button>
      </div>
    </div>
  `,
    )
    .join("");
}

async function openRegisterBoardModal() {
  // Populate user dropdown
  // const userSelect = document.getElementById("board-user");
  // const res = await fetchData("get_all_users");

  // userSelect.innerHTML = "";
  // if (userSelect) {
  //   userSelect.innerHTML =
  //     '<option value="">-- Select User --</option>' +
  //     res.data
  //       .map((u) => `<option value="${u.id_users}">${u.email}</option>`)
  //       .join("");
  // }

  openModal("register-board-modal");
}

async function openPairBoardModal(boardId) {
  
  activeBoardId = boardId;
  document.getElementById("pair-board-id").value = boardId;
  const res = await fetchData(`get_all_boards&id=${boardId}`);

  const board = res.data[0];

  document.getElementById("pair-board-info").textContent =
    `Pair board ${board.name} (${board.id}) with a user`;

  const userSelect = document.getElementById("pair-user-select");
  if (userSelect) {
    const res = await fetchData("get_unpaired_users");
    userSelect.innerHTML =
      '<option value="">-- Select User --</option>' +
      res.data.map((u) => `<option value="${u.id_users}">${u.email}</option>`).join("");
  }

  openModal("pair-board-modal");
}

async function unpairBoard(boardId,userId) {
  if (!confirm(`Are you sure you want to unpair this ${boardId}, ${userId} board?`)) return;
  const res = await postData("unpair_board", {
    action: "unpair_board",
    board_id: boardId,
    user_id: userId,
  });
  if (!res.success) {
    showCustomModal("Unpairing Error", res.message);
    return;
  }
  // Implement API call to unpair board
  notify("Board unpaired successfully");
  await fetchBoards();
}

async function editBoard(boardId) {
  const res = await fetchData(`get_all_boards&id=${boardId}`);
  const board = res.data[0];

  openModal("edit-board-modal");
   document.getElementById("edit-board-id").value = board.id
   document.getElementById("edit-board-name").value = board.name
   document.getElementById("edit-board-location").value = board.location
   document.getElementById("edit-board-user").value = board.paired_user_id
    
  // notify("Edit board feature coming soon");
}

// ============================================
// SUBSCRIPTION PLANS MANAGEMENT
// ============================================

async function fetchPlans() {
  try {
    // Try to fetch from API

    const res = await fetchData("get_plans");

    if (res.success) {
      plans = res.data;
      renderPlansGrid(plans);
    } else {
      // Use mock data if API not ready
      useMockPlans();
    }
  } catch (err) {

    useMockPlans();
  }

  renderPlansGrid();
}

function useMockPlans() {
  plans = [
    {
      id: "1",
      name: "Free Trial",
      price: 0,
      duration: 30,
      type: "free_trial",
      description: "Perfect for testing our platform and exploring features",
      features: ["1 User", "Basic Support", "1 Board", "30-day access"],
      isPopular: false,
      isActive: true,
    },
    {
      id: "2",
      name: "Standard Plan",
      price: 130000,
      duration: 365,
      type: "standard",
      description: "Our most popular plan for growing teams",
      features: [
        "5 Users",
        "Email Support",
        "5 Boards",
        "Analytics Dashboard",
        "Monthly Reports",
      ],
      isPopular: true,
      isActive: true,
    },
    {
      id: "3",
      name: "Premium Plan",
      price: 250000,
      duration: 365,
      type: "premium",
      description: "Advanced features for professional teams",
      features: [
        "Unlimited Users",
        "24/7 Priority Support",
        "Unlimited Boards",
        "Advanced Analytics",
        "Custom Integrations",
        "API Access",
      ],
      isPopular: false,
      isActive: true,
    },
    {
      id: "4",
      name: "Enterprise",
      price: 500000,
      duration: 365,
      type: "enterprise",
      description: "Custom solutions for large organizations",
      features: [
        "Unlimited Everything",
        "Dedicated Support",
        "Custom Development",
        "SLA Guarantee",
        "White Label Options",
        "On-premise Deployment",
      ],
      isPopular: false,
      isActive: false,
    },
  ];
}

function renderPlansGrid(plansData = plans) {
  const grid = document.getElementById("plans-grid");
  if (!grid) return;

  if (plansData.length === 0) {
    grid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
        <i class="fa-solid fa-tags" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
        <p style="color: var(--text-secondary); font-size: 1.125rem;">No subscription plans yet</p>
        <button class="btn-primary" style="margin-top: 1rem;" data-action="create-plan">
          <i class="fa-solid fa-plus"></i> Create Your First Plan
        </button>
      </div>
    `;
    return;
  }

  grid.innerHTML = "";

  plansData.forEach((plan) => {
    // Fix: Check if features is a string and parse it, or default to an empty array
    let featuresArray = [];
    if (Array.isArray(plan.features)) {
      featuresArray = plan.features;
    } else if (typeof plan.features === "string") {
      try {
        featuresArray = JSON.parse(plan.features);
      } catch (e) {
        // If it's a comma-separated string instead of JSON
        featuresArray = plan.features.split(",").map((f) => f.trim());
      }
    }

    const featuresHTML =
      featuresArray.length > 0
        ? `<ul class="plan-features">
        ${featuresArray.map((feature) => `<li>${feature}</li>`).join("")}
      </ul>`
        : "";

    const planCard = `
    <div class="plan-card ${plan.isPopular ? "popular" : ""} ${plan.isActive ? "inactive" : ""}">
      <div class="plan-header">
        <h3 class="plan-name">${plan.name}</h3>
        <div class="plan-price">
          <span class="amount">RWF ${formatCurrency(plan.price)}</span>
        </div>
        <p class="plan-duration">Per ${plan.duration} Months</p>
        <span class="plan-type">${plan.type_plan}</span>
      </div>
      
      ${plan.description ? `<p class="plan-description">${plan.description}</p>` : ""}
      
      ${featuresHTML}
      
      <div class="plan-actions">
        <button class="btn-edit" data-action="edit-plan" data-plan-id="${plan.sub_id}">
          <i class="fa-solid fa-pencil"></i> Edit
        </button>

        <button class="btn-delete" data-action="delete-plan" data-plan-id="${plan.sub_id}">
          <i class="fa-solid fa-trash"></i> Delete
        </button>
      </div>
    </div>
  `;

    grid.innerHTML += planCard;
  });
}

function openCreatePlanModal() {
  isEditMode = false;
  activePlanId = null;
  document.getElementById("plan-modal-title").textContent =
    "Create Subscription Plan";
  document.getElementById("plan-form").reset();
  document.getElementById("plan-active").checked = true;
  openModal("plan-modal");
}

async function editPlan(planId) {
  isEditMode = true;
  const res = await fetchData(`get_plans&id=${planId}`);

  // Access the first element of the data array
  const plan = res.success && res.data.length > 0 ? res.data[0] : null;

  if (!plan) {
    notify("Plan not found");
    return;
  }

  // Use the correct keys from your backend response
  document.getElementById("plan-modal-title").textContent =
    "Edit Subscription Plan";
  document.getElementById("plan-name").value = plan.name;
  document.getElementById("plan-price").value = plan.price;
  document.getElementById("plan-duration").value = plan.duration;


  // Note: Backend uses 'type_plan', frontend logic uses 'type'
  document.getElementById("plan-type").value = plan.type_plan || plan.type;

  document.getElementById("plan-description").value = plan.description || "";

  // Handle features parsing (if it's a string from DB)
  let features = plan.features;
  if (typeof features === "string") {
    try {
      features = JSON.parse(features);
    } catch (e) {
      features = features.split(",");
    }
  }

  document.getElementById("plan-features").value = Array.isArray(features)
    ? features.join("\n")
    : "";

  document.getElementById("plan-popular").checked =
    plan.isPopular == true || plan.is_popular == 1;
  document.getElementById("plan-active").checked =
    plan.isActive == true || plan.is_active == 1;
  activePlanId = plan.sub_id;
  openModal("plan-modal");
}

async function togglePlanStatus(planId) {
  const plan = plans.find((p) => p.id === planId);
  if (!plan) return;

  const newStatus = !plan.isActive;

  try {
    const res = await fetch(PHP_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `action=toggle_plan_status&plan_id=${planId}&status=${newStatus ? 1 : 0}`,
    });

    const raw = await res.text();
    const data = JSON.parse(raw);

    if (data.success) {
      notify(`Plan ${newStatus ? "activated" : "deactivated"} successfully`);
      await fetchPlans();
    } else {
      showCustomModal("Toggle Error", data.message);
    }
  } catch (err) {
    console.error("Error toggling plan:", err);
    // Mock success for demo
    plan.isActive = newStatus;
    notify(
      `Plan ${newStatus ? "activated" : "deactivated"} successfully (Demo)`,
    );
    renderPlansGrid();
  }
}

async function deletePlan(planId) {
  if (
    !confirm(
      `Are you sure you want to delete "${planId}"? This action cannot be undone.`,
    )
  ) {
    return;
  }

  try {
    const res = await deleteData(`delete_plan`, {
      id: planId,
      action: "delete_plan",
    });
    const data = res;

    if (data.success) {
      notify("Plan deleted successfully");
      await fetchPlans();
    } else {
      showCustomModal("Delete Error", data.message);
    }
  } catch (err) {
    console.error("Error deleting plan:", err);
    renderPlansGrid();
  }
}

function formatCurrency(amount) {
  if (amount === 0) return "Free";
  return new Intl.NumberFormat("en-RW").format(amount);
}

// ============================================
// ANALYTICS
// ============================================

async function renderAnalytics() {
  // Revenue Chart
  const revenueCtx = document.getElementById("revenueChart");
  if (revenueCtx && !revenueChart) {
    const res = await fetchData("get_monthly_revenue");

    revenueChart = new Chart(revenueCtx, {
      type: "bar",
      data: {
        labels: res.data.labels || ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
        datasets: [
          {
            label: "Revenue (RWF)",
            data: res.data.data || [1200000, 1500000, 1800000, 2100000, 2400000, 2700000],
            backgroundColor: "#fce24e",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: "#f1f5f9",
            },
          },
        },
      },
    });
  }

  // Board Status Chart
  const boardCtx = document.getElementById("boardStatusChart");
  if (boardCtx && !boardStatusChart) {
    boardStatusChart = new Chart(boardCtx, {
      type: "pie",
      data: {
        labels: ["Online", "Offline"],
        datasets: [
          {
            data:[45,87] || [boardsStatus[1], boardsStatus[0] - boardsStatus[1]],
            backgroundColor: ["#facc15", "#efe5c0"],
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
          },
        },
      },
    });
  }

  // Retention Chart
  const retentionCtx = document.getElementById("retentionChart");
  if (retentionCtx && !retentionChart) {
    const res = await fetchData("get_retention");

    retentionChart = new Chart(retentionCtx, {
      type: "line",
      data: {
        labels: res.data.labels || [
          "Month 1",
          "Month 2",
          "Month 3",
          "Month 4",
          "Month 5",
          "Month 6",
        ],
        datasets: [
          {
            label: "Retention Rate (%)",
            data: res.data.data || [100, 85, 78, 72, 68, 65],
            borderColor: "#facc15",
            backgroundColor: "rgb(253, 224, 71,0.1)",
            tension: 0.4,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            grid: {
              color: "#f1f5f9",
            },
          },
        },
      },
    });
  }
}

function updateAnalytics() {
  const startDate = document.getElementById("start-date").value;
  const endDate = document.getElementById("end-date").value;

  if (!startDate || !endDate) {
    notify("Please select both start and end dates");
    return;
  }

  notify("Analytics updated for selected date range");
  // Implement actual data fetching and chart updates
}

// ============================================
// MODALS
// ============================================

function openModal(modalId) {
  document.getElementById("modal-backdrop").classList.add("active");
  document.getElementById(modalId).style.display = "block";
}

function closeModal() {
  document.getElementById("modal-backdrop").classList.remove("active");
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.style.display = "none";
  });
}

function openRenewModal(id) {
  activeId = id;
  renderPlans();
  document.getElementById("renew-target").innerText =
    `Extend subscription for user#${id}`;
  openModal("renew-modal");
}

function openPwdModal(id) {
  activeId = id;
  openModal("pwd-modal");
}

function openAddUserModal() {
  notify("Add user feature coming soon");
}

function openBulkRenewModal() {
  notify("Bulk renew feature coming soon");
}

function exportData() {
  notify("Exporting data...");
  // Implement data export functionality
}

// ============================================
// ACTIONS
// ============================================

async function renderPlans() {
  const res = await fetchData("get_plans");
  const container = document.getElementById("renew-plans-container");
  container.innerHTML = "";
  if (res.success) {
    plans = res.data;

    plans.forEach((option) => {
      const card = document.createElement("div");
      card.id = option.sub_id;
      card.className = "option-card";

      card.innerHTML = `
    <div>
      <span class="option-title">${option.name}</span>
      <span class="option-desc">${option.description}</span>
    </div>
    <span class="option-price">${option.price}</span>
  `;
      card.addEventListener("click", () => { 
        renewSubscription(option.sub_id);
      });
      container.appendChild(card);
    });
  } else {
    showCustomModal("Error", "Could not fetch plans.");
  }
}


async function renewSubscription(plan_id) {
  
  if (!activeId) {
    notify("No user selected for renewal");
    return;
  }
  const  res = await postData("update_subscription", {
    action: "update_subscription",
    plan_type: plan_id,
    user_id: activeId
  });
  if (res.success) {
    closeModal();
    notify("Subscription renewed successfully");
    await renderStats();
    await setFilter(filter);
  }
  else {
     showCustomModal("Renewal Error", res.message);
  }

}


async function processRenewal(planType) {
  try {
    const res = await fetch(PHP_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `action=update_subscription&plan_type=${encodeURIComponent(
        planType,
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

async function processPwd() {
  const newPassword = document.getElementById("new-pwd").value;
  if (!newPassword) {
    notify("Password cannot be empty");
    return;
  }
  const res = await postData("reset_password_user", {
    action: "reset_password_user",
    new_password: newPassword,
    user_id: activeId,
  });
  if (res.success) {
    closeModal();
    document.getElementById("new-pwd").value = "";
    notify("Password reset successfully");
  }
  else {
    showCustomModal("Error", res.message);
  }  
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
      setTimeout(() => {
        window.location.href = "../index.html";
      }, 1000);
    } else {
      showCustomModal("Logout Error", data.message);
    }
  } catch (err) {
    showCustomModal("Network Error", "Could not log out.");
  }
}

// ============================================
// UTILITIES
// ============================================

function notify(msg) {
  const container = document.getElementById("toast-container");
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.innerHTML = `<i class="fa-solid fa-circle-check toast-icon"></i> <span>${msg}</span>`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

function showCustomModal(title, message) {
  alert(`${title}: ${message}`);
}

function getLast30Days() {
  const days = [];
  for (let i = 29; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    days.push(
      date.toLocaleDateString("en-US", { month: "short", day: "numeric" }),
    );
  }
  return days;
}

function generateMockData(count, min, max) {
  return Array.from(
    { length: count },
    () => Math.floor(Math.random() * (max - min + 1)) + min,
  );
}

// ============================================
// EVENT LISTENERS
// ============================================


const searchInput = document.getElementById("search");
const userList = document.getElementById("user-list");

searchInput.addEventListener("keyup", () => {
  const filter = searchInput.value.toLowerCase();
  const rows = userList.getElementsByTagName("tr");

  Array.from(rows).forEach((row) => {
    const emailCell = row.querySelector('td[data-label="User"] .text-bold');
    if (emailCell) {
      const emailText = emailCell.textContent || emailCell.innerText;
      row.style.display = emailText.toLowerCase().includes(filter)
        ? ""
        : "none";
    }
  });
});



function setupEventListeners() {
  // Search
  const searchInput = document.getElementById("search");
  if (searchInput) {
    searchInput.addEventListener("input", async (e) => {
      searchQuery = e.target.value;
      await setFilter(filter);
    });
  }

  // Filter buttons (delegated)
  const filterBar = document.getElementById("filter-bar");
  if (filterBar) {
    filterBar.addEventListener("click", async (e) => {
      const btn = e.target.closest("button[data-filter]");
      if (!btn) return;
      await setFilter(btn.dataset.filter);
    });
  }

  // User list actions (delegated)
  const userList = document.getElementById("user-list");
  if (userList) {
    userList.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      const id = btn.dataset.userId;

      if (btn.dataset.action === "renew") openRenewModal(id);
      if (btn.dataset.action === "pwd") openPwdModal(id);
    });
  }

  // Renew modal buttons
  const renewTrial = document.getElementById("renew-trial");
  if (renewTrial) {
    renewTrial.addEventListener("click", () => processRenewal("free trial"));
  }

  const renewStandard = document.getElementById("renew-standard");
  if (renewStandard) {
    renewStandard.addEventListener("click", () => processRenewal("standard"));
  }

  const cancelRenew = document.getElementById("cancel-renew");
  if (cancelRenew) {
    cancelRenew.addEventListener("click", closeModal);
  }

  // Password modal buttons
  const updatePwd = document.getElementById("update-pwd");
  if (updatePwd) {
    updatePwd.addEventListener("click", processPwd);
  }

  const cancelPwd = document.getElementById("cancel-pwd");
  if (cancelPwd) {
    cancelPwd.addEventListener("click", closeModal);
  }

  // Register board form
  const registerBoardForm = document.getElementById("register-board-form");
  if (registerBoardForm) {
    registerBoardForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const boardData = {
        // id: document.getElementById("board-id").value,
        action: "register_board",
        name: document.getElementById("board-name").value,
        location: document.getElementById("board-location").value,
        password: document.getElementById("board-password").value,
      };

      // Implement API call to register board

      const res = await postData("register_board", boardData);
      if (res.success) {
        notify("Board registered successfully");
        closeModal();
        registerBoardForm.reset();
        await fetchBoards();
      }
      else {
        showCustomModal("Registration Error", res.message);
      }
      
    });
  }

   const editBoardForm = document.getElementById("edit-board-form");
   if (editBoardForm) {
     editBoardForm.addEventListener("submit", async (e) => {
       e.preventDefault();

       const boardData = {
         // id: document.getElementById("board-id").value,
         action: "edit_board",
         id: document.getElementById("edit-board-id").value,
         name: document.getElementById("edit-board-name").value,
         location: document.getElementById("edit-board-location").value,
         password: document.getElementById("edit-board-password").value,
       };
  
       // Implement API call to register board

       const res = await updateData("edit_board", boardData);
       if (res.success) {
         notify("Board edited successfully");
         closeModal();
         editBoardForm.reset();
         await fetchBoards();
       } else {
         showCustomModal("Edit Error", res.message);
       }
     });
   }
  // Plan form submission
  const planForm = document.getElementById("plan-form");
  if (planForm) {
    planForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const features = document
        .getElementById("plan-features")
        .value.split("\n")
        .map((f) => f.trim())
        .filter((f) => f.length > 0);

      const planData = {
        action: isEditMode ? "update_plan" : "create_plan",
        plan_name: document.getElementById("plan-name").value,
        price: parseInt(document.getElementById("plan-price").value),
        duration_days:
          parseInt(document.getElementById("plan-duration").value) * 30,
        type_plan: document.getElementById("plan-type").value,
        description: document.getElementById("plan-description").value,
        features: JSON.stringify(features),
        is_popular: document.getElementById("plan-popular").checked ? 1 : 0,
        is_active: document.getElementById("plan-active").checked ? 1 : 0,
        id: isEditMode ? activePlanId : undefined,
      };
      
      try {
        const result =
          planData["action"] === "update_plan"
            ? await updateData("update_plan", planData)
            : await postData("create_plan", planData);

        if (result.success) {
          notify(
            isEditMode
              ? "Plan updated successfully"
              : "Plan created successfully",
          );
          closeModal();
          await fetchPlans();
        }
      } catch (error) {
        showCustomModal("Error", error.message);
      }
    });
  }

  // Pair board button
  const confirmPair = document.getElementById("confirm-pair");
  if (confirmPair) {
    confirmPair.addEventListener("click", async () => {
      const userId = document.getElementById("pair-user-select").value;
      if (!userId) {
        notify("Please select a user");
        return;
      }
      const res = await postData("pair_board", {
        action: "pair_board",
        board_id: activeBoardId,
        user_id: userId,
      });
      if (!res.success) {
        showCustomModal("Pairing Error", res.message);
        return;
      }
      notify("Board paired successfully");
      closeModal();
      await fetchBoards();
    });
  }

  // Logout button
  const logoutBtn = document.getElementById("logout");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", async () => {
      await logout();
    });
  }

  // Backdrop click closes modal
  const modalBackdrop = document.getElementById("modal-backdrop");
  if (modalBackdrop) {
    modalBackdrop.addEventListener("click", (e) => {
      if (e.target === e.currentTarget) closeModal();
    });
  }

  // Board filters
  document.addEventListener("click", (e) => {
    if (e.target.matches("[data-board-filter]")) {
      const filter = e.target.dataset.boardFilter;
      document.querySelectorAll("[data-board-filter]").forEach((btn) => {
        btn.classList.remove("active");
      });
      e.target.classList.add("active");

      // Filter boards based on status
      // Implement filtering logic
    }
  });

  // Global event delegation for all data-action buttons
  document.addEventListener("click", async (e) => {
    const actionBtn = e.target.closest("[data-action]");
    if (!actionBtn) return;

    const action = actionBtn.dataset.action;

    switch (action) {
      // Navigation actions
      case "goto-users":
        showPage("users");
        break;
      case "goto-boards":
        showPage("boards");
        break;

      // Quick actions
      case "bulk-renew":
        openBulkRenewModal();
        break;
      case "export-data":
        exportData();
        break;

      // User actions
      case "add-user":
        openAddUserModal();
        break;

      // Board actions
      case "register-board":
        openRegisterBoardModal();
        break;
      case "pair-board":
        openPairBoardModal(actionBtn.dataset.boardId);
        break;
      case "unpair-board":
        await unpairBoard(actionBtn.dataset.boardId,actionBtn.dataset.pairId);
        break;
      case "edit-board":
        editBoard(actionBtn.dataset.boardId);
        break;

      // Plan actions
      case "create-plan":
        openCreatePlanModal();
        break;
      case "edit-plan":
        editPlan(actionBtn.dataset.planId);
        break;
      case "toggle-plan":
        await togglePlanStatus(actionBtn.dataset.planId);
        break;
      case "delete-plan":
        await deletePlan(actionBtn.dataset.planId);
        break;

      // Analytics actions
      case "update-analytics":
        updateAnalytics();
        break;

      // Modal actions
      case "close-modal":
        closeModal();
        break;
    }
  });
}

// ============================================
// INITIALIZATION
// ============================================



admin.init();
async function init() {
  setupNavigation();
  setupEventListeners();
  await renderDashboard();
  admin.onGlobalUpdate = (stats) => {
    console.clear();
    boardsStatus[1] = stats.totalOnline;
    console.table(stats.deviceList);
  };
}

// Start the app when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
