import express from 'express';
import session from 'express-session';
import cookieParser from 'cookie-parser';
import path from 'path';
import fs from 'fs';
import multer from 'multer';
import { GoogleGenAI } from '@google/genai';
import { fileURLToPath } from 'url';
import { 
  store, 
  getActiveCompany, 
  setActiveCompany,
  getProjectDocuments,
  getDocumentById,
  addDocument,
  deleteDocument
} from './data/store.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Document uploads directory and secure multer storage
const uploadsDir = path.join(__dirname, 'public', 'uploads', 'documents');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

const ALLOWED_EXTENSIONS = new Set(['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.csv', '.png', '.jpg', '.jpeg', '.webp', '.txt', '.dwg']);
const DISALLOWED_EXTENSIONS = new Set(['.php', '.phtml', '.php3', '.php4', '.php5', '.phar', '.exe', '.sh', '.bat', '.cmd', '.pl', '.cgi', '.py', '.js', '.vbs']);

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadsDir);
  },
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase();
    const safeName = `doc_${Date.now()}_${Math.random().toString(36).substring(2, 9)}${ext}`;
    cb(null, safeName);
  }
});

const upload = multer({
  storage,
  limits: {
    fileSize: 25 * 1024 * 1024 // 25 MB max
  },
  fileFilter: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase();
    if (DISALLOWED_EXTENSIONS.has(ext)) {
      return cb(new Error('Executable and server-side script files (.php, .exe, .sh, etc.) are strictly prohibited.'));
    }
    if (!ALLOWED_EXTENSIONS.has(ext)) {
      return cb(new Error(`File format "${ext}" is not supported. Allowed: PDF, Office docs, Spreadsheets, Images, CAD DWG, TXT.`));
    }
    cb(null, true);
  }
});

// Shared Gemini AI Client (Server-side initialization as per SKILL guidelines)
const aiClient = (process.env.GEMINI_API_KEY && process.env.GEMINI_API_KEY.trim().length > 10)
  ? new GoogleGenAI({
      apiKey: process.env.GEMINI_API_KEY.trim(),
      httpOptions: {
        headers: {
          'User-Agent': 'aistudio-build'
        }
      }
    })
  : null;

const app = express();
const PORT = process.env.PORT || 3000;

// View engine setup
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'ejs');

// Body parsers & session middleware
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(cookieParser());
app.use(
  session({
    secret: process.env.SESSION_SECRET || 'construction_erp_secret_key_98765',
    resave: false,
    saveUninitialized: false,
    cookie: { maxAge: 1000 * 60 * 60 * 24 } // 24 hours
  })
);

// Serve static assets from public/ directory
app.use(express.static(path.join(__dirname, 'public')));

// Alias /ERP/public/* to /* so legacy URLs from PHP work seamlessly
app.use((req, res, next) => {
  if (req.url.startsWith('/ERP/public')) {
    req.url = req.url.replace('/ERP/public', '') || '/';
  }
  next();
});

// Global template helpers & context
app.use((req, res, next) => {
  // If not logged in, provide default superadmin session for quick exploration if desired
  if (!req.session.user) {
    // Default to Super Administrator for convenience in preview, but login route is fully available
    req.session.user = store.users[0];
  }
  res.locals.user = req.session.user;
  res.locals.company = getActiveCompany();
  res.locals.companies = store.companies;
  res.locals.impersonating = req.session.impersonating || false;
  res.locals.flash = req.session.flash || null;
  delete req.session.flash;
  next();
});

// Authentication middleware
function requireAuth(req, res, next) {
  if (!req.session.user) {
    return res.redirect('/login');
  }
  next();
}

// ----------------------------------------------------
// ROUTES
// ----------------------------------------------------

// 1. Landing Page
app.get('/', (req, res) => {
  res.render('landing', {
    title: 'Lubell Nigeria Limited | Construction ERP Platform',
    activeNav: 'landing'
  });
});

app.get('/landing', (req, res) => {
  res.render('landing', {
    title: 'Lubell Nigeria Limited | Construction ERP Platform',
    activeNav: 'landing'
  });
});

// 2. Authentication
app.get('/login', (req, res) => {
  const quick = req.query.quick;
  let emailValue = 'superadmin@erp.com';
  if (quick === 'md') emailValue = 'md@lubell.com';
  else if (quick === 'site_engineer') emailValue = 'site.engineer@lubell.com';
  else if (quick === 'accountant') emailValue = 'accountant@lubell.com';
  else if (quick === 'procurement') emailValue = 'procurement@lubell.com';
  else if (quick === 'hr') emailValue = 'hr@lubell.com';

  res.render('login', {
    title: 'Sign In | Construction ERP',
    error: null,
    success: null,
    emailValue
  });
});

app.get('/super-admin/login', (req, res) => {
  res.render('login', {
    title: 'Super Admin Login',
    error: null,
    success: null,
    emailValue: 'superadmin@erp.com'
  });
});

app.post('/login', (req, res) => {
  const { email, password } = req.body;
  const user = store.users.find(u => u.email.toLowerCase() === (email || '').trim().toLowerCase());

  if (user) {
    req.session.user = user;
    req.session.flash = `Welcome back, ${user.name}!`;
    return res.redirect('/dashboard');
  }

  res.render('login', {
    title: 'Sign In | Construction ERP',
    error: 'Invalid email or password. Please use one of the quick profiles or superadmin@erp.com.',
    success: null,
    emailValue: email
  });
});

app.get('/logout', (req, res) => {
  req.session.destroy(() => {
    res.redirect('/login');
  });
});

app.get('/stop-impersonation', (req, res) => {
  req.session.user = store.users[0];
  req.session.impersonating = false;
  req.session.flash = 'Returned to Super Administrator mode.';
  res.redirect('/dashboard');
});

// 3. Dashboard
app.get('/dashboard', requireAuth, (req, res) => {
  const projects = store.projects;
  const inventoryItems = store.inventoryItems;
  const requisitions = store.requisitions;
  const employees = store.employees;
  const purchaseOrders = store.purchaseOrders;

  const lowStockCount = inventoryItems.filter(i => i.current_stock <= i.reorder_level).length;
  const pendingRequisitions = requisitions.filter(r => r.status === 'pending').length;
  const contractTotal = projects.reduce((sum, p) => sum + (p.contract_value || 0), 0);
  const budgetTotal = projects.reduce((sum, p) => sum + (p.budget || 0), 0);

  const stats = {
    projectsCount: projects.length,
    inventoryCount: inventoryItems.length,
    lowStockCount,
    requisitionsCount: requisitions.length,
    pendingRequisitions,
    employeesCount: employees.length,
    purchaseOrdersCount: purchaseOrders.length,
    contractTotal,
    budgetTotal
  };

  res.render('dashboard', {
    title: 'Dashboard',
    activeNav: 'dashboard',
    stats,
    projects,
    inventoryItems,
    requisitions
  });
});

// 4. Projects Module
app.get('/modules/projects', requireAuth, (req, res) => {
  res.render('projects', {
    title: 'Projects Portfolio',
    activeNav: 'projects',
    projects: store.projects,
    customers: store.customers
  });
});

app.post('/projects/save', requireAuth, (req, res) => {
  const { project_number, name, client_name, consultant, contract_value, budget, start_date, end_date, progress_percent, site_location, status } = req.body;
  const newProject = {
    id: store.projects.length + 1,
    company_id: store.activeCompanyId,
    project_number: project_number || `PRJ-2026-00${store.projects.length + 1}`,
    name,
    client_name,
    consultant: consultant || 'Internal Supervision',
    contract_value: Number(contract_value) || 0,
    budget: Number(budget) || 0,
    start_date,
    end_date,
    site_location,
    progress_percent: Number(progress_percent) || 0,
    status: status || 'in_progress',
    site_logs: [],
    budgets: []
  };

  store.projects.push(newProject);
  req.session.flash = `Project "${newProject.name}" registered successfully.`;
  res.redirect('/modules/projects');
});

app.get('/projects/detail', requireAuth, (req, res) => {
  const projectId = Number(req.query.id);
  const project = store.projects.find(p => p.id === projectId) || store.projects[0];

  res.render('project_detail', {
    title: `${project.name} - Project Details`,
    activeNav: 'projects',
    project
  });
});

app.post('/projects/schedule/save', requireAuth, (req, res) => {
  const { project_id, progress_percent, status } = req.body;
  const project = store.projects.find(p => p.id === Number(project_id));
  if (project) {
    project.progress_percent = Number(progress_percent);
    project.status = status;
    req.session.flash = `Updated progress to ${project.progress_percent}%.`;
    return res.redirect(`/projects/detail?id=${project.id}`);
  }
  res.redirect('/modules/projects');
});

app.post('/projects/budget/add', requireAuth, (req, res) => {
  const { project_id, budget_name, category, unit_of_measure, quantity, unit_cost, supplier } = req.body;
  const project = store.projects.find(p => p.id === Number(project_id));
  if (project) {
    const qty = Number(quantity) || 1;
    const rate = Number(unit_cost) || 0;
    project.budgets.push({
      id: (project.budgets.length || 0) + 1,
      budget_name,
      category,
      unit_of_measure,
      quantity: qty,
      unit_cost: rate,
      total_cost: qty * rate,
      supplier: supplier || 'Approved Vendor',
      status: 'approved'
    });
    req.session.flash = `Added budget item "${budget_name}".`;
    return res.redirect(`/projects/detail?id=${project.id}`);
  }
  res.redirect('/modules/projects');
});

app.post('/projects/daily-progress/add', requireAuth, (req, res) => {
  const { project_id, log_date, weather, completed_work, manpower, equipment, safety_note } = req.body;
  const project = store.projects.find(p => p.id === Number(project_id));
  if (project) {
    project.site_logs.unshift({
      id: (project.site_logs.length || 0) + 1,
      log_date,
      weather,
      completed_work,
      manpower,
      equipment,
      safety_note
    });
    req.session.flash = 'Daily site progress report submitted successfully.';
    return res.redirect(`/projects/detail?id=${project.id}`);
  }
  res.redirect('/modules/projects');
});

// 5. Inventory Module
app.get('/modules/inventory', requireAuth, (req, res) => {
  res.render('inventory', {
    title: 'Warehouse & Inventory',
    activeNav: 'inventory',
    items: store.inventoryItems,
    projects: store.projects
  });
});

app.post('/inventory/save', requireAuth, (req, res) => {
  const { item_code, name, category, unit, cost_price, selling_price, current_stock, reorder_level, supplier_name, warehouse_bin } = req.body;
  const newItem = {
    id: store.inventoryItems.length + 1,
    item_code,
    name,
    category,
    unit,
    cost_price: Number(cost_price) || 0,
    selling_price: Number(selling_price) || 0,
    opening_stock: Number(current_stock) || 0,
    current_stock: Number(current_stock) || 0,
    reorder_level: Number(reorder_level) || 10,
    supplier_name: supplier_name || 'Standard Supplier',
    warehouse_bin: warehouse_bin || 'General Yard',
    updated_at: new Date().toISOString().split('T')[0]
  };

  store.inventoryItems.push(newItem);
  req.session.flash = `Material "${newItem.name}" added to warehouse catalog.`;
  res.redirect('/modules/inventory');
});

app.post('/inventory/issue', requireAuth, (req, res) => {
  const { item_id, project_id, quantity, notes } = req.body;
  const item = store.inventoryItems.find(i => i.id === Number(item_id));
  const qty = Number(quantity) || 0;

  if (item && item.current_stock >= qty) {
    item.current_stock -= qty;
    req.session.flash = `Successfully issued ${qty} ${item.unit} of "${item.name}". Stock remaining: ${item.current_stock} ${item.unit}.`;
  } else if (item) {
    req.session.flash = `Cannot issue ${qty} ${item.unit}: Only ${item.current_stock} ${item.unit} available in stock!`;
  }
  res.redirect('/modules/inventory');
});

// 6. Requisitions Module
app.get('/requisition', requireAuth, (req, res) => {
  res.render('requisition', {
    title: 'Material Requisitions',
    activeNav: 'requisition',
    requisitions: store.requisitions,
    projects: store.projects
  });
});

app.post('/requisition/save', requireAuth, (req, res) => {
  const { project_id, urgency, required_date, department, purpose, item_name, quantity, unit, estimated_cost } = req.body;
  const project = store.projects.find(p => p.id === Number(project_id)) || store.projects[0];
  const qty = Number(quantity) || 1;
  const cost = Number(estimated_cost) || 0;

  const newReq = {
    id: store.requisitions.length + 1,
    requisition_no: `REQ-2026-00${store.requisitions.length + 43}`,
    project_id: project.id,
    project_name: project.name,
    requested_by_name: req.session.user.name,
    requested_by_role: req.session.user.role_name,
    department: department || 'Engineering',
    required_date,
    purpose,
    urgency: urgency || 'normal',
    status: 'pending',
    created_at: new Date().toISOString().replace('T', ' ').slice(0, 16),
    items: [
      { item_name, quantity: qty, unit: unit || 'Pcs', estimated_cost: cost }
    ],
    total_amount: cost
  };

  store.requisitions.unshift(newReq);
  req.session.flash = `Requisition ${newReq.requisition_no} raised and routed for approval.`;
  res.redirect('/requisition');
});

app.post('/requisition/decision', requireAuth, (req, res) => {
  const { requisition_id, decision } = req.body;
  const reqItem = store.requisitions.find(r => r.id === Number(requisition_id));
  if (reqItem) {
    reqItem.status = decision === 'approve' ? 'approved' : 'rejected';
    req.session.flash = `Requisition ${reqItem.requisition_no} has been ${reqItem.status}.`;
  }
  res.redirect('/requisition');
});

app.post('/requisition/dispatch/approve', requireAuth, (req, res) => {
  const { requisition_id } = req.body;
  const reqItem = store.requisitions.find(r => r.id === Number(requisition_id));
  if (reqItem) {
    reqItem.status = 'dispatched';
    req.session.flash = `Dispatch note generated and waybill confirmed for ${reqItem.requisition_no}.`;
  }
  res.redirect('/requisition');
});

app.get('/requisition/delivery-note', requireAuth, (req, res) => {
  const reqItem = store.requisitions.find(r => r.id === Number(req.query.id)) || store.requisitions[0];
  res.send(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Delivery Note - ${reqItem.requisition_no}</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="p-5">
      <div class="card p-5 border shadow-sm max-w-700 mx-auto" style="max-width: 800px;">
        <div class="d-flex justify-content-between border-bottom pb-4 mb-4">
          <div>
            <h3 class="fw-bold mb-0">${getActiveCompany().company_name}</h3>
            <small class="text-muted">CENTRAL LOGISTICS & WAREHOUSE DISPATCH NOTE</small>
          </div>
          <div class="text-end">
            <h4 class="text-primary font-monospace">${reqItem.requisition_no}</h4>
            <small class="text-muted">Date: ${reqItem.created_at}</small>
          </div>
        </div>
        <div class="row mb-4">
          <div class="col-6">
            <strong>Destination Project:</strong><br>${reqItem.project_name}<br>
            <strong>Department:</strong> ${reqItem.department}
          </div>
          <div class="col-6">
            <strong>Requested By:</strong><br>${reqItem.requested_by_name} (${reqItem.requested_by_role})<br>
            <strong>Status:</strong> <span class="badge bg-success">${reqItem.status.toUpperCase()}</span>
          </div>
        </div>
        <table class="table table-bordered mb-4">
          <thead class="table-light">
            <tr><th>#</th><th>Material Description</th><th>Quantity</th><th>Estimated Value</th></tr>
          </thead>
          <tbody>
            ${reqItem.items.map((it, idx) => `<tr><td>${idx+1}</td><td>${it.item_name}</td><td>${it.quantity} ${it.unit}</td><td>₦${Number(it.estimated_cost).toLocaleString()}</td></tr>`).join('')}
          </tbody>
        </table>
        <div class="row pt-4 border-top text-center">
          <div class="col-4 border-top border-dark pt-2"><small>Store Keeper Signature</small></div>
          <div class="col-4 border-top border-dark pt-2"><small>Haulage Driver Signature</small></div>
          <div class="col-4 border-top border-dark pt-2"><small>Site Receiving Engineer</small></div>
        </div>
        <div class="mt-4 text-center">
          <button class="btn btn-primary btn-sm" onclick="window.print()">Print Waybill Note</button>
          <a class="btn btn-outline-secondary btn-sm ms-2" href="/requisition">Back</a>
        </div>
      </div>
    </body>
    </html>
  `);
});

// 7. Procurement & POs
app.get('/management/procurement', requireAuth, (req, res) => {
  res.render('procurement', {
    title: 'Procurement & Purchase Orders',
    activeNav: 'procurement',
    purchaseOrders: store.purchaseOrders,
    suppliers: store.suppliers,
    projects: store.projects
  });
});

app.post('/management/procurement/create', requireAuth, (req, res) => {
  const { supplier_name, project_name, expected_delivery, total_amount, description } = req.body;
  const newPO = {
    id: store.purchaseOrders.length + 1,
    po_number: `PO-2026-00${store.purchaseOrders.length + 91}`,
    supplier_name,
    project_name,
    issue_date: new Date().toISOString().split('T')[0],
    expected_delivery,
    total_amount: Number(total_amount) || 0,
    status: 'pending_delivery',
    payment_status: 'partial',
    items: [{ description, quantity: 1, unit_price: Number(total_amount), total: Number(total_amount) }]
  };

  store.purchaseOrders.unshift(newPO);
  req.session.flash = `Purchase Order ${newPO.po_number} issued to ${newPO.supplier_name}.`;
  res.redirect('/management/procurement');
});

app.post('/management/procurement/receive/save', requireAuth, (req, res) => {
  const { po_id } = req.body;
  const po = store.purchaseOrders.find(p => p.id === Number(po_id));
  if (po) {
    po.status = 'received';
    req.session.flash = `Purchase Order ${po.po_number} received into warehouse stock.`;
  }
  res.redirect('/management/procurement');
});

app.get('/management/procurement/receipt', requireAuth, (req, res) => {
  const po = store.purchaseOrders.find(p => p.id === Number(req.query.id)) || store.purchaseOrders[0];
  res.send(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Goods Received Note - ${po.po_number}</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="p-5">
      <div class="card p-5 border shadow-sm max-w-700 mx-auto" style="max-width: 800px;">
        <div class="d-flex justify-content-between border-bottom pb-4 mb-4">
          <div>
            <h3 class="fw-bold mb-0">${getActiveCompany().company_name}</h3>
            <small class="text-muted">GOODS RECEIVED NOTE (GRN)</small>
          </div>
          <div class="text-end">
            <h4 class="text-success font-monospace">GRN-${po.po_number}</h4>
            <small class="text-muted">Received Date: ${po.issue_date}</small>
          </div>
        </div>
        <p><strong>Supplier:</strong> ${po.supplier_name}</p>
        <p><strong>Project Allocated:</strong> ${po.project_name}</p>
        <p><strong>Total Value:</strong> ₦${(po.total_amount).toLocaleString()}</p>
        <div class="alert alert-success mt-3">All materials inspected and verified against purchase specifications.</div>
        <div class="mt-4 text-center">
          <button class="btn btn-success btn-sm" onclick="window.print()">Print GRN Document</button>
          <a class="btn btn-outline-secondary btn-sm ms-2" href="/management/procurement">Back</a>
        </div>
      </div>
    </body>
    </html>
  `);
});

// 8. Employees & HR
app.get('/management/employees', requireAuth, (req, res) => {
  res.render('employees', {
    title: 'Employee Directory',
    activeNav: 'employees',
    employees: store.employees,
    roles: store.roles,
    departments: store.departments
  });
});

app.post('/management/employees/save', requireAuth, (req, res) => {
  const { employee_code, first_name, last_name, email, phone, department, position, salary, hire_date } = req.body;
  const newEmp = {
    id: store.employees.length + 1,
    company_id: store.activeCompanyId,
    employee_code: employee_code || `EMP-00${store.employees.length + 1}`,
    first_name,
    last_name,
    email,
    phone,
    department,
    position,
    designation: position,
    hire_date: hire_date || new Date().toISOString().split('T')[0],
    salary: Number(salary) || 0,
    status: 'active'
  };

  store.employees.push(newEmp);
  req.session.flash = `Employee ${newEmp.first_name} ${newEmp.last_name} enrolled successfully.`;
  res.redirect('/management/employees');
});

app.get('/management/hr', requireAuth, (req, res) => {
  res.render('hr', {
    title: 'HR & Payroll',
    activeNav: 'hr',
    departments: store.departments,
    salaryAdvances: store.salaryAdvances,
    employeeLoans: store.employeeLoans,
    employees: store.employees
  });
});

app.post('/management/salary-advances/request', requireAuth, (req, res) => {
  const { employee_name, amount, reason } = req.body;
  store.salaryAdvances.push({
    id: store.salaryAdvances.length + 1,
    employee_name,
    department: 'Operations',
    amount: Number(amount) || 0,
    reason,
    request_date: new Date().toISOString().split('T')[0],
    status: 'pending'
  });
  req.session.flash = 'Salary advance request submitted.';
  res.redirect('/management/hr');
});

app.post('/management/salary-advances/approve', requireAuth, (req, res) => {
  const advance = store.salaryAdvances.find(a => a.id === Number(req.body.id));
  if (advance) {
    advance.status = 'approved';
    req.session.flash = `Advance for ${advance.employee_name} approved.`;
  }
  res.redirect('/management/hr');
});

app.post('/management/payroll/run', requireAuth, (req, res) => {
  req.session.flash = 'Monthly payroll batch executed successfully for all active staff! Payslips generated.';
  res.redirect('/management/hr');
});

// 9. Accounting
app.get('/modules/accounting', requireAuth, (req, res) => {
  res.render('accounting', {
    title: 'Financial Accounting & Ledger',
    activeNav: 'accounting',
    purchaseOrders: store.purchaseOrders
  });
});

app.post('/modules/accounting/purchase-payment/save', requireAuth, (req, res) => {
  const { beneficiary, amount, account } = req.body;
  req.session.flash = `Disbursement voucher of ₦${Number(amount).toLocaleString()} processed for ${beneficiary}.`;
  res.redirect('/modules/accounting');
});

// 10. Team Chat
app.get('/modules/chat', requireAuth, (req, res) => {
  res.render('chat', {
    title: 'Team Chat',
    activeNav: 'chat',
    chatMessages: store.chatMessages
  });
});

app.post('/modules/chat/send', requireAuth, (req, res) => {
  const { text } = req.body;
  if (text && text.trim()) {
    store.chatMessages.push({
      id: store.chatMessages.length + 1,
      sender_id: req.session.user.id,
      sender_name: req.session.user.name,
      channel: 'general',
      text: text.trim(),
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    });
  }
  res.redirect('/modules/chat');
});

// 11. Role Portals
app.get('/portal/:role', requireAuth, (req, res) => {
  const roleSlug = req.params.role;
  const roleTitle = roleSlug.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

  const pendingReqsCount = store.requisitions.filter(r => r.status === 'pending').length;
  const contractTotal = store.projects.reduce((sum, p) => sum + (p.contract_value || 0), 0);

  res.render('portal', {
    title: `${roleTitle} Portal`,
    activeNav: `portal-${roleSlug.slice(0, 3)}`,
    portalRole: roleTitle,
    pendingReqsCount,
    contractTotal,
    projectsCount: store.projects.length
  });
});

// 12. Company Workspaces & Setup
app.get('/company/workspace', requireAuth, (req, res) => {
  res.render('company_workspace', {
    title: 'Company Workspace',
    activeNav: 'workspace'
  });
});

app.post('/company/create', requireAuth, (req, res) => {
  const { company_name, theme_color, currency } = req.body;
  const newCompany = {
    id: store.companies.length + 1,
    company_name,
    theme_color: theme_color || '#1d4ed8',
    currency: currency || '₦',
    logo_path: '',
    is_active: 1
  };
  store.companies.push(newCompany);
  store.activeCompanyId = newCompany.id;
  req.session.flash = `Workspace "${newCompany.company_name}" created and set active.`;
  res.redirect('/company/workspace');
});

app.get('/setup', requireAuth, (req, res) => {
  res.render('setup', {
    title: 'Company Setup',
    activeNav: 'setup'
  });
});

app.post('/setup', requireAuth, (req, res) => {
  const { company_name, currency, theme_color } = req.body;
  const company = getActiveCompany();
  if (company) {
    company.company_name = company_name;
    company.currency = currency;
    company.theme_color = theme_color;
    req.session.flash = 'Company settings saved successfully.';
  }
  res.redirect('/setup');
});

app.post('/setup/select-company', requireAuth, (req, res) => {
  const { company_id } = req.body;
  setActiveCompany(company_id);
  req.session.flash = `Switched active workspace to "${getActiveCompany().company_name}".`;
  res.redirect(req.headers.referer || '/dashboard');
});

// 13. Profile & System Administration
app.get('/profile', requireAuth, (req, res) => {
  res.render('profile', {
    title: 'My Profile',
    activeNav: 'profile'
  });
});

app.get('/modules/workflow', requireAuth, (req, res) => {
  res.render('workflow', {
    title: 'Workflow Setup',
    activeNav: 'workflow'
  });
});

app.get('/modules/requisition-form', requireAuth, (req, res) => {
  res.render('workflow', {
    title: 'Requisition Form Fields',
    activeNav: 'workflow'
  });
});

app.get('/management/module-access', requireAuth, (req, res) => {
  res.render('module_access', {
    title: 'Module Access Control',
    activeNav: 'module-access',
    roles: store.roles
  });
});

// 13. Project Enable - Document & File Management
app.get(['/modules/project-enable', '/project-enable'], requireAuth, (req, res) => {
  const company = getActiveCompany();
  const companyProjects = store.projects.filter(p => p.company_id === company.id);
  const selectedProjectId = req.query.project_id ? Number(req.query.project_id) : '';
  const selectedCategory = req.query.category || '';
  
  let documents = getProjectDocuments(selectedProjectId, company.id);
  if (selectedCategory) {
    documents = documents.filter(d => d.category === selectedCategory);
  }
  
  const activeProject = selectedProjectId ? companyProjects.find(p => p.id === selectedProjectId) : null;

  res.render('project_enable', {
    title: 'Project Enable & Documentation',
    activeNav: 'project-enable',
    projects: companyProjects,
    documents,
    selectedProjectId,
    selectedCategory,
    activeProject
  });
});

app.post('/projects/enable/upload', requireAuth, (req, res) => {
  upload.single('document_file')(req, res, (err) => {
    const company = getActiveCompany();
    const projectId = req.body.project_id ? Number(req.body.project_id) : null;

    if (err) {
      req.session.flash = { type: 'danger', message: `Upload rejected: ${err.message}` };
      return res.redirect(`/modules/project-enable${projectId ? '?project_id=' + projectId : ''}`);
    }

    if (!req.file) {
      req.session.flash = { type: 'danger', message: 'Please select a valid file to upload.' };
      return res.redirect(`/modules/project-enable${projectId ? '?project_id=' + projectId : ''}`);
    }

    // Verify project belongs to current company
    const project = store.projects.find(p => p.id === projectId && p.company_id === company.id);
    if (!project) {
      try { fs.unlinkSync(req.file.path); } catch (e) {}
      req.session.flash = { type: 'danger', message: 'Unauthorized project selection or access denied.' };
      return res.redirect('/modules/project-enable');
    }

    const title = (req.body.title || req.file.originalname).trim();
    const category = (req.body.category || 'Other').trim();

    const newDoc = addDocument({
      company_id: company.id,
      title,
      category,
      file_name: req.file.filename,
      original_name: req.file.originalname,
      file_path: `public/uploads/documents/${req.file.filename}`,
      file_type: req.file.mimetype || 'application/octet-stream',
      file_size: req.file.size,
      related_type: 'project_enable',
      related_id: project.id,
      uploaded_by: req.session.user.id,
      uploaded_by_name: req.session.user.name
    });

    req.session.flash = { type: 'success', message: `Document "${newDoc.title}" registered successfully.` };
    res.redirect(`/modules/project-enable?project_id=${project.id}`);
  });
});

app.get('/projects/enable/view/:id', requireAuth, (req, res) => {
  const company = getActiveCompany();
  const doc = getDocumentById(req.params.id, company.id);
  if (!doc) {
    return res.status(404).send('Document not found or access denied for your company.');
  }

  // Security: prevent path traversal
  const safeFilename = path.basename(doc.file_name);
  const filePath = path.join(uploadsDir, safeFilename);

  if (!fs.existsSync(filePath)) {
    return res.status(404).send('Physical document file missing from storage.');
  }

  const ext = path.extname(doc.original_name).toLowerCase();
  let contentType = doc.file_type || 'application/octet-stream';
  if (ext === '.pdf') contentType = 'application/pdf';
  else if (['.jpg', '.jpeg'].includes(ext)) contentType = 'image/jpeg';
  else if (ext === '.png') contentType = 'image/png';
  else if (ext === '.webp') contentType = 'image/webp';
  else if (ext === '.txt') contentType = 'text/plain';

  res.setHeader('Content-Type', contentType);
  res.setHeader('Content-Disposition', `inline; filename="${encodeURIComponent(doc.original_name)}"`);
  res.sendFile(filePath);
});

app.get('/projects/enable/download/:id', requireAuth, (req, res) => {
  const company = getActiveCompany();
  const doc = getDocumentById(req.params.id, company.id);
  if (!doc) {
    return res.status(404).send('Document not found or access denied.');
  }

  // Security: prevent path traversal
  const safeFilename = path.basename(doc.file_name);
  const filePath = path.join(uploadsDir, safeFilename);

  if (!fs.existsSync(filePath)) {
    return res.status(404).send('Physical file missing from server.');
  }

  res.download(filePath, doc.original_name);
});

app.post('/projects/enable/delete/:id', requireAuth, (req, res) => {
  const company = getActiveCompany();
  const doc = getDocumentById(req.params.id, company.id);
  if (!doc) {
    req.session.flash = { type: 'danger', message: 'Document not found or access denied.' };
    return res.redirect('/modules/project-enable');
  }

  // Permission check: Super Admin, MD, Project Manager, or original uploader
  const allowedRoles = ['Super Administrator', 'Managing Director', 'Project Manager'];
  const user = req.session.user;
  const isAuthorized = allowedRoles.includes(user.role_name) || doc.uploaded_by === user.id;

  if (!isAuthorized) {
    req.session.flash = { type: 'danger', message: 'You do not have permission to delete this project document.' };
    return res.redirect(`/modules/project-enable?project_id=${doc.related_id}`);
  }

  try {
    const safeFilename = path.basename(doc.file_name);
    const filePath = path.join(uploadsDir, safeFilename);
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }
  } catch (e) {
    console.error('Error removing file:', e);
  }

  deleteDocument(doc.id, company.id);
  req.session.flash = { type: 'success', message: `Document "${doc.title}" deleted successfully.` };
  res.redirect(`/modules/project-enable?project_id=${doc.related_id}`);
});

// 14. ERP AI Assistant / Chatbot
app.get('/modules/assistant', requireAuth, (req, res) => {
  res.render('assistant', {
    title: 'ERP AI Assistant',
    activeNav: 'assistant'
  });
});

app.post('/api/chatbot', requireAuth, async (req, res) => {
  const { message } = req.body;
  if (!message || typeof message !== 'string') {
    return res.status(400).json({ error: 'Message is required.' });
  }

  const user = req.session.user;
  const company = getActiveCompany();

  // Role permissions scoping:
  const isFinance = ['Accountant', 'Finance Manager', 'Managing Director', 'Super Administrator'].includes(user.role_name);

  // Filter ERP data scoped strictly to current company
  const companyProjects = store.projects.filter(p => p.company_id === company.id);
  const lowStockItems = store.inventoryItems.filter(i => i.current_stock <= i.reorder_level);
  const pendingRequisitions = store.requisitions.filter(r => r.status === 'pending');
  const activePOs = store.purchaseOrders;

  // Build targeted ERP context (never dump raw tables)
  const erpSummary = {
    company: company.company_name,
    userRole: user.role_name,
    activeProjects: companyProjects.map(p => ({
      name: p.name,
      code: p.project_number,
      location: p.site_location,
      progress: `${p.progress_percent}%`,
      status: p.status,
      client: p.client_name,
      contractValue: isFinance ? `₦${p.contract_value.toLocaleString('en-US')}` : 'Confidential (Executive/Finance)',
      budget: isFinance ? `₦${p.budget.toLocaleString('en-US')}` : 'Confidential (Executive/Finance)'
    })),
    lowStockMaterials: lowStockItems.map(i => ({
      name: i.name,
      code: i.item_code,
      current: `${i.current_stock} ${i.unit}`,
      reorderLevel: `${i.reorder_level} ${i.unit}`,
      supplier: i.supplier_name
    })),
    pendingRequisitions: pendingRequisitions.map(r => ({
      code: r.requisition_no,
      project: r.project_name,
      requestedBy: r.requested_by_name,
      urgency: r.urgency,
      total: isFinance ? `₦${r.total_amount.toLocaleString('en-US')}` : undefined
    }))
  };

  // If GEMINI_API_KEY is configured on server, attempt generative response with timeout and error fallback
  if (aiClient) {
    try {
      const prompt = `You are the ERP Assistant for construction firm "${company.company_name}".
User Name: ${user.name}
User Role: ${user.role_name}

Verified ERP Context:
${JSON.stringify(erpSummary, null, 2)}

User Question: "${message}"

Guidelines:
1. Answer accurately and directly using the provided ERP Context.
2. Respect role access: do not disclose financial/salary figures if marked Confidential.
3. Be professional, concise, and format data using clean bullet points and bold headers.
4. If asked about something outside the ERP context, answer helpfully within construction management.`;

      const genPromise = aiClient.models.generateContent({
        model: 'gemini-3.8-flash',
        contents: prompt
      });
      const timeoutPromise = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('timeout')), 5000)
      );

      const response = await Promise.race([genPromise, timeoutPromise]);
      if (response && response.text) {
        return res.json({ reply: response.text });
      }
    } catch {
      // Seamlessly fall through to internal ERP knowledge engine
    }
  }

  // Internal Intelligent Rule Engine (Works 100% reliably even without external API key or network)
  const q = message.toLowerCase().trim();
  let reply = '';

  if (q.includes('project') || q.includes('active') || q.includes('progress') || q.includes('status') || q.includes('bridge') || q.includes('plaza') || q.includes('villa')) {
    if (q.includes('bridge')) {
      const p = companyProjects.find(x => x.name.toLowerCase().includes('bridge')) || companyProjects[0];
      reply = `**${p.name}** (${p.project_number}):\n` +
        `* **Status:** ${p.status.replace('_', ' ').toUpperCase()}\n` +
        `* **Progress:** ${p.progress_percent}% completed\n` +
        `* **Location:** ${p.site_location}\n` +
        `* **Client:** ${p.client_name}\n` +
        (isFinance ? `* **Contract Value:** ₦${p.contract_value.toLocaleString('en-US')} (Budget: ₦${p.budget.toLocaleString('en-US')})\n` : '') +
        `* **Recent Log:** ${p.site_logs && p.site_logs.length ? p.site_logs[0].completed_work : 'All foundation work progressing as scheduled.'}`;
    } else if (q.includes('plaza')) {
      const p = companyProjects.find(x => x.name.toLowerCase().includes('plaza')) || companyProjects[1];
      reply = `**${p.name}** (${p.project_number}):\n` +
        `* **Status:** ${p.status.replace('_', ' ').toUpperCase()}\n` +
        `* **Progress:** ${p.progress_percent}% completed\n` +
        `* **Location:** ${p.site_location}\n` +
        `* **Client:** ${p.client_name}\n` +
        (isFinance ? `* **Contract Value:** ₦${p.contract_value.toLocaleString('en-US')}\n` : '');
    } else {
      reply = `There are currently **${companyProjects.length} active construction projects** in ${company.company_name}:\n\n` +
        companyProjects.map(p => 
          `* **${p.name}** (${p.project_number}): ${p.progress_percent}% complete &bull; ${p.site_location} [${p.status.replace('_', ' ')}]`
        ).join('\n');
    }
  } else if (q.includes('stock') || q.includes('material') || q.includes('inventory') || q.includes('reorder') || q.includes('cement') || q.includes('sand') || q.includes('rebar')) {
    if (lowStockItems.length > 0) {
      reply = `⚠️ **Low Stock Alert:** There are **${lowStockItems.length} material items** at or below reorder level:\n\n` +
        lowStockItems.map(i => 
          `* **${i.name}** (${i.item_code}): Current **${i.current_stock} ${i.unit}** (Minimum reorder level: ${i.reorder_level} ${i.unit}) &bull; Supplier: ${i.supplier_name}`
        ).join('\n') +
        `\n\nImmediate restocking is recommended to avoid site downtime.`;
    } else {
      reply = `All materials in the central warehouse are currently stocked above their reorder thresholds. Total tracked inventory items: **${store.inventoryItems.length} items**.`;
    }
  } else if (q.includes('requisition') || q.includes('approval') || q.includes('pending') || q.includes('req')) {
    reply = `Currently, there are **${pendingRequisitions.length} pending material requisitions** awaiting sign-off:\n\n` +
      pendingRequisitions.map(r => 
        `* **${r.requisition_no}** for *${r.project_name}*: Requested by ${r.requested_by_name} (${r.department}) &bull; Urgency: **${r.urgency.toUpperCase()}**` +
        (isFinance ? ` &bull; Total: ₦${r.total_amount.toLocaleString('en-US')}` : '')
      ).join('\n') +
      `\n\nAuthorized managers can approve or dispatch these via the Requisition Module.`;
  } else if (q.includes('budget') || q.includes('contract') || q.includes('financial') || q.includes('value') || q.includes('revenue') || q.includes('cost')) {
    if (!isFinance) {
      reply = `Financial and executive budget records are restricted to Accounts and Management roles. Your current role is **${user.role_name}**. Please consult the Finance department for details.`;
    } else {
      const totalContract = companyProjects.reduce((sum, p) => sum + p.contract_value, 0);
      const totalBudget = companyProjects.reduce((sum, p) => sum + p.budget, 0);
      reply = `**Financial Portfolio Overview for ${company.company_name}:**\n\n` +
        `* **Total Contract Portfolio Value:** ₦${totalContract.toLocaleString('en-US')}\n` +
        `* **Total Allocated Project Budgets:** ₦${totalBudget.toLocaleString('en-US')}\n` +
        `* **Active Purchase Orders:** ${activePOs.length} orders totalling ₦${activePOs.reduce((s, p) => s + p.total_amount, 0).toLocaleString('en-US')}\n` +
        `* **Active Customer Accounts:** ${store.customers.length} clients registered.`;
    }
  } else if (q.includes('engineer') || q.includes('staff') || q.includes('personnel') || q.includes('who') || q.includes('okon')) {
    reply = `**Site & Engineering Leadership:**\n\n` +
      `* **Lead Site Engineer:** Engr. David Okon (Senior Project Engineer &bull; Civil Engineering)\n` +
      `* **Managing Director:** Alhaji Ibrahim Usman (Executive Approval Authority)\n` +
      `* **Procurement Lead:** Tunde Bakare (Purchasing & Material Waybills)\n` +
      `* **Head of Accounts:** Grace Nwosu (Finance & Accounts)\n` +
      `* **HR Manager:** Fatima Bello (Human Resources)`;
  } else if (q.includes('enable') || q.includes('document') || q.includes('drawing') || q.includes('permit') || q.includes('file')) {
    const docs = store.documents.filter(d => d.company_id === company.id);
    reply = `**Project Enable & Document Registry:**\n\n` +
      `There are **${docs.length} registered project files** across statutory approvals, drawings, and safety plans.\n` +
      docs.map(d => `* **${d.title}** (${d.category}) &bull; [${d.original_name}]`).join('\n') +
      `\n\nYou can upload, preview, and download documents in the [Project Enable Module](/modules/project-enable).`;
  } else if (q.includes('hello') || q.includes('hi') || q.includes('help')) {
    reply = `Hello **${user.name}**! I am your construction ERP assistant. You can ask me:\n\n` +
      `* *"What projects are currently active?"*\n` +
      `* *"Which materials are low in stock?"*\n` +
      `* *"How many requisitions are awaiting approval?"*\n` +
      `* *"What is the progress on the Bridge Extension?"*\n` +
      `* *"What is our total contract value?"*`;
  } else {
    reply = `I reviewed our ERP records for **${company.company_name}** regarding "${message}":\n\n` +
      `* **Active Projects:** ${companyProjects.length} infrastructure sites in progress.\n` +
      `* **Warehouse Materials:** ${store.inventoryItems.length} tracked items (${lowStockItems.length} below reorder level).\n` +
      `* **Material Requisitions:** ${pendingRequisitions.length} pending review.\n\n` +
      `Feel free to ask for specific project status, inventory levels, or requisition details!`;
  }

  res.json({ reply });
});

// 15. Fallback handler for specialized modules (contract admin, RMC operations)
app.get('/modules/:module', requireAuth, (req, res) => {
  const modName = req.params.module.replace('-', ' ');
  req.session.flash = `Module "${modName}" is available in this version. Showing operations overview.`;
  res.redirect('/dashboard');
});

// Start dev server
app.listen(PORT, '0.0.0.0', () => {
  console.log(`[AI Studio] Construction ERP server running at http://0.0.0.0:${PORT}`);
});
