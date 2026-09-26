// In-memory data store for Construction ERP System

export const store = {
  activeCompanyId: 1,
  companies: [
    {
      id: 1,
      company_name: 'Lubell Nigeria Limited',
      logo_path: 'company/company_logo_c14808dea463fa0109d08049.jpg',
      theme_color: '#1d4ed8',
      currency: '₦',
      is_active: 1
    },
    {
      id: 2,
      company_name: "Nayomie's Stitches & Construction",
      logo_path: 'company/company_logo_b3caef1d7311a02a290ad072.webp',
      theme_color: '#bb1676',
      currency: '₦',
      is_active: 1
    }
  ],

  roles: [
    { id: 1, name: 'Super Administrator', description: 'System super administrator with full permissions' },
    { id: 31, name: 'Managing Director', description: 'Executive leadership and company-wide approval authority' },
    { id: 32, name: 'Finance Manager', description: 'Financial planning, budgets and accounting controls' },
    { id: 33, name: 'HR Manager', description: 'Human resources, staff management and payroll' },
    { id: 34, name: 'Project Manager', description: 'Project execution, scheduling and site supervision' },
    { id: 35, name: 'Site Engineer', description: 'On-site technical supervision, site logs and quality' },
    { id: 36, name: 'Store Officer', description: 'Store operations, stock receipts and dispatches' },
    { id: 37, name: 'Procurement Officer', description: 'Vendor relations, purchasing and purchase orders' },
    { id: 38, name: 'Accountant', description: 'Bookkeeping, invoicing and payment processing' },
    { id: 44, name: 'Staff', description: 'General employee portal access' },
    { id: 47, name: 'Logistics Officer', description: 'Fleet, transportation and delivery tracking' }
  ],

  users: [
    {
      id: 1,
      company_id: 1,
      name: 'Super Administrator',
      email: 'superadmin@erp.com',
      password: 'SuperAdmin123!',
      role_id: 1,
      role_name: 'Super Administrator',
      employee_id: 1
    },
    {
      id: 2,
      company_id: 1,
      name: 'Alhaji Ibrahim Usman',
      email: 'md@lubell.com',
      password: 'Password123!',
      role_id: 31,
      role_name: 'Managing Director',
      employee_id: 2
    },
    {
      id: 3,
      company_id: 1,
      name: 'Engr. David Okon',
      email: 'site.engineer@lubell.com',
      password: 'Password123!',
      role_id: 35,
      role_name: 'Site Engineer',
      employee_id: 3
    },
    {
      id: 4,
      company_id: 1,
      name: 'Grace Nwosu',
      email: 'accountant@lubell.com',
      password: 'Password123!',
      role_id: 38,
      role_name: 'Accountant',
      employee_id: 4
    },
    {
      id: 5,
      company_id: 1,
      name: 'Tunde Bakare',
      email: 'procurement@lubell.com',
      password: 'Password123!',
      role_id: 37,
      role_name: 'Procurement Officer',
      employee_id: 5
    },
    {
      id: 6,
      company_id: 1,
      name: 'Fatima Bello',
      email: 'hr@lubell.com',
      password: 'Password123!',
      role_id: 33,
      role_name: 'HR Manager',
      employee_id: 6
    }
  ],

  employees: [
    {
      id: 1,
      company_id: 1,
      employee_code: 'EMP-001',
      first_name: 'System',
      last_name: 'Administrator',
      email: 'superadmin@erp.com',
      phone: '+234 802 000 0001',
      department: 'Management',
      position: 'Super Administrator',
      designation: 'Executive IT & Operations',
      hire_date: '2024-01-15',
      salary: 1200000,
      status: 'active'
    },
    {
      id: 2,
      company_id: 1,
      employee_code: 'EMP-002',
      first_name: 'Ibrahim',
      last_name: 'Usman',
      email: 'md@lubell.com',
      phone: '+234 803 111 2222',
      department: 'Executive',
      position: 'Managing Director',
      designation: 'Chief Executive Officer',
      hire_date: '2020-03-01',
      salary: 2500000,
      status: 'active'
    },
    {
      id: 3,
      company_id: 1,
      employee_code: 'EMP-003',
      first_name: 'David',
      last_name: 'Okon',
      email: 'site.engineer@lubell.com',
      phone: '+234 805 333 4444',
      department: 'Civil Engineering',
      position: 'Site Engineer',
      designation: 'Senior Project Engineer',
      hire_date: '2022-06-10',
      salary: 850000,
      status: 'active'
    },
    {
      id: 4,
      company_id: 1,
      employee_code: 'EMP-004',
      first_name: 'Grace',
      last_name: 'Nwosu',
      email: 'accountant@lubell.com',
      phone: '+234 807 555 6666',
      department: 'Finance & Accounts',
      position: 'Accountant',
      designation: 'Head of Accounts',
      hire_date: '2022-09-01',
      salary: 750000,
      status: 'active'
    },
    {
      id: 5,
      company_id: 1,
      employee_code: 'EMP-005',
      first_name: 'Tunde',
      last_name: 'Bakare',
      email: 'procurement@lubell.com',
      phone: '+234 809 777 8888',
      department: 'Procurement & Logistics',
      position: 'Procurement Officer',
      designation: 'Purchasing Lead',
      hire_date: '2023-02-15',
      salary: 650000,
      status: 'active'
    },
    {
      id: 6,
      company_id: 1,
      employee_code: 'EMP-006',
      first_name: 'Fatima',
      last_name: 'Bello',
      email: 'hr@lubell.com',
      phone: '+234 811 999 0000',
      department: 'Human Resources',
      position: 'HR Manager',
      designation: 'Personnel Lead',
      hire_date: '2023-05-12',
      salary: 700000,
      status: 'active'
    }
  ],

  projects: [
    {
      id: 1,
      company_id: 1,
      project_number: 'PRJ-TEST-1001',
      name: 'Bridge Extension & Dualization',
      client_id: 1,
      client_name: 'Federal Ministry of Works / IHVN',
      consultant: 'Arup Nigeria Ltd',
      contract_value: 125000000,
      budget: 95000000,
      start_date: '2025-08-18',
      end_date: '2027-02-28',
      site_location: 'Central Business District, Abuja',
      progress_percent: 42,
      status: 'in_progress',
      site_logs: [
        {
          id: 1,
          log_date: '2026-09-24',
          weather: 'Sunny (32°C)',
          completed_work: 'Pier foundation concrete curing completed. Steel rebar tie-up for superstructure span underway.',
          manpower: '28 workers (4 masons, 12 iron benders, 12 labourers)',
          equipment: '1x 50T Crane, 2x Concrete Mixer, 1x Excavator',
          safety_note: 'Daily toolbox safety meeting completed. All PPE compliant.'
        }
      ],
      budgets: [
        { id: 1, budget_name: 'Structural Concrete Grade 35', category: 'Materials', unit_of_measure: 'm³', quantity: 450, unit_cost: 65000, total_cost: 29250000, supplier: 'Dangote ReadyMix', status: 'approved' },
        { id: 2, budget_name: 'High-Tensile Deformed Rebar (16mm-25mm)', category: 'Materials', unit_of_measure: 'Ton', quantity: 85, unit_cost: 880000, total_cost: 74800000, supplier: 'Tiger Steel Nigeria', status: 'approved' },
        { id: 3, budget_name: 'Site Labour & Subcontracting', category: 'Labour', unit_of_measure: 'Man-months', quantity: 24, unit_cost: 450000, total_cost: 10800000, supplier: 'Sub-craft Guild', status: 'approved' }
      ]
    },
    {
      id: 2,
      company_id: 1,
      project_number: 'PRJ-LOG-2001',
      name: 'Commercial Mixed-Use Plaza',
      client_id: 1,
      client_name: 'IHVN Properties Ltd',
      consultant: 'Spectrum Design Consult',
      contract_value: 280000000,
      budget: 210000000,
      start_date: '2025-11-01',
      end_date: '2026-12-18',
      site_location: 'Wuse II, Abuja',
      progress_percent: 68,
      status: 'in_progress',
      site_logs: [
        {
          id: 1,
          log_date: '2026-09-25',
          weather: 'Partly Cloudy',
          completed_work: 'Second floor curtain wall glazing installation and internal electrical conduit roughing.',
          manpower: '34 workers',
          equipment: 'Scaffolding towers, 2x boom lifts, welding sets',
          safety_note: 'Full harness inspection verified for all work at heights.'
        }
      ],
      budgets: [
        { id: 4, budget_name: 'Curtain Wall Glazing & Aluminium Cladding', category: 'Façade', unit_of_measure: 'm²', quantity: 600, unit_cost: 75000, total_cost: 45000000, supplier: 'GlassTech Nig', status: 'approved' },
        { id: 5, budget_name: 'HVAC Ducting & Chillers', category: 'MEP', unit_of_measure: 'Lot', quantity: 1, unit_cost: 38000000, total_cost: 38000000, supplier: 'Carrier West Africa', status: 'approved' }
      ]
    },
    {
      id: 3,
      company_id: 1,
      project_number: 'PRJ-RES-3002',
      name: 'Maitama Luxury Residential Villas',
      client_id: 1,
      client_name: 'Apex Horizon Estates',
      consultant: 'Studio Archiform',
      contract_value: 450000000,
      budget: 340000000,
      start_date: '2026-02-15',
      end_date: '2027-08-30',
      site_location: 'Maitama Hills, Abuja',
      progress_percent: 18,
      status: 'in_progress',
      site_logs: [],
      budgets: [
        { id: 6, budget_name: 'Foundation Retaining Walls & Piling', category: 'Substructure', unit_of_measure: 'Lin.m', quantity: 180, unit_cost: 140000, total_cost: 25200000, supplier: 'Geotech Foundation Ltd', status: 'approved' }
      ]
    }
  ],

  customers: [
    { id: 1, customer_code: 'IHVN-1787044615', company_name: 'IHVN Properties Ltd', contact_person: 'Dr. Patrick Okoro', email: 'pat.okoro@ihvn.org', phone: '+234 803 444 5555', address: 'Plot 252 Cadastral Zone, Abuja', balance: 14500000, status: 'active' },
    { id: 2, customer_code: 'FMW-202409', company_name: 'Federal Ministry of Works', contact_person: 'Engr. Babatunde Sanusi', email: 'works.procure@fmw.gov.ng', phone: '+234 802 888 9999', address: 'Mabushi Headquarters, Abuja', balance: 42000000, status: 'active' },
    { id: 3, customer_code: 'AHE-202511', company_name: 'Apex Horizon Estates', contact_person: 'Mrs. Aisha Danjuma', email: 'aisha@apexhorizon.com', phone: '+234 806 222 3333', address: 'Maitama District, Abuja', balance: 0, status: 'active' }
  ],

  suppliers: [
    { id: 1, supplier_code: 'SUP-001', company_name: 'Dangote Cement PLC', contact_person: 'Chidi Amadi', email: 'orders@dangote.com', phone: '+234 800 326 4683', address: 'Lagos-Ibadan Expressway, Ogun', balance: 0, status: 'active' },
    { id: 2, supplier_code: 'SUP-002', company_name: 'Tiger Steel Nigeria Ltd', contact_person: 'Musa Garba', email: 'sales@tigersteel.ng', phone: '+234 803 765 4321', address: 'Idu Industrial Area, Abuja', balance: 5400000, status: 'active' },
    { id: 3, supplier_code: 'SUP-003', company_name: 'BuildMart Supply Depot', contact_person: 'Emeka Eze', email: 'orders@buildmart.ng', phone: '+234 802 123 4567', address: 'Dei-Dei Building Materials Market, Abuja', balance: 1200000, status: 'active' }
  ],

  inventoryItems: [
    {
      id: 1,
      item_code: 'INV-CEM-001',
      name: 'Dangote Falcon Cement 42.5R (50kg)',
      category: 'Binding Materials',
      unit: 'Bags',
      supplier_name: 'Dangote Cement PLC',
      cost_price: 10500,
      selling_price: 12500,
      opening_stock: 500,
      current_stock: 380,
      reorder_level: 100,
      warehouse_bin: 'Bay A-12',
      updated_at: '2026-09-24'
    },
    {
      id: 2,
      item_code: 'INV-STL-016',
      name: 'High-Yield Rebar 16mm TMT (12m length)',
      category: 'Steel & Metals',
      unit: 'Lengths',
      supplier_name: 'Tiger Steel Nigeria Ltd',
      cost_price: 14200,
      selling_price: 16800,
      opening_stock: 300,
      current_stock: 195,
      reorder_level: 50,
      warehouse_bin: 'Open Yard 3',
      updated_at: '2026-09-22'
    },
    {
      id: 3,
      item_code: 'INV-STL-012',
      name: 'High-Yield Rebar 12mm TMT (12m length)',
      category: 'Steel & Metals',
      unit: 'Lengths',
      supplier_name: 'Tiger Steel Nigeria Ltd',
      cost_price: 8900,
      selling_price: 10500,
      opening_stock: 450,
      current_stock: 82,
      reorder_level: 80,
      warehouse_bin: 'Open Yard 4',
      updated_at: '2026-09-25'
    },
    {
      id: 4,
      item_code: 'INV-AGR-001',
      name: 'Granite Stone 3/4 Inch (20mm Aggregate)',
      category: 'Aggregates',
      unit: 'Tons',
      supplier_name: 'Mpape Quarry Works',
      cost_price: 12000,
      selling_price: 14500,
      opening_stock: 200,
      current_stock: 140,
      reorder_level: 40,
      warehouse_bin: 'Silo 2',
      updated_at: '2026-09-23'
    },
    {
      id: 5,
      item_code: 'INV-SND-001',
      name: 'Sharp River Sand (Fine Sand for plaster/screed)',
      category: 'Aggregates',
      unit: 'Tons',
      supplier_name: 'Niger Sand Dredgers',
      cost_price: 9500,
      selling_price: 11000,
      opening_stock: 150,
      current_stock: 25,
      reorder_level: 30,
      warehouse_bin: 'Silo 1',
      updated_at: '2026-09-25'
    },
    {
      id: 6,
      item_code: 'INV-PPE-001',
      name: 'Standard Industrial Hard Hat (ANSI Z89.1)',
      category: 'Safety & PPE',
      unit: 'Pieces',
      supplier_name: 'BuildMart Supply Depot',
      cost_price: 4500,
      selling_price: 6000,
      opening_stock: 100,
      current_stock: 75,
      reorder_level: 25,
      warehouse_bin: 'Shelf C-04',
      updated_at: '2026-09-20'
    }
  ],

  requisitions: [
    {
      id: 1,
      requisition_no: 'REQ-2026-0042',
      project_id: 1,
      project_name: 'Bridge Extension & Dualization',
      requested_by_name: 'Engr. David Okon',
      requested_by_role: 'Site Engineer',
      department: 'Civil Engineering',
      required_date: '2026-10-02',
      purpose: 'Urgent concrete pour for foundation pier caps at Section 3.',
      urgency: 'high',
      status: 'pending',
      created_at: '2026-09-25 09:30',
      items: [
        { item_name: 'Dangote Falcon Cement 42.5R (50kg)', quantity: 150, unit: 'Bags', estimated_cost: 1575000 },
        { item_name: 'Granite Stone 3/4 Inch (20mm Aggregate)', quantity: 30, unit: 'Tons', estimated_cost: 360000 }
      ],
      total_amount: 1935000
    },
    {
      id: 2,
      requisition_no: 'REQ-2026-0041',
      project_id: 2,
      project_name: 'Commercial Mixed-Use Plaza',
      requested_by_name: 'Tunde Bakare',
      requested_by_role: 'Procurement Officer',
      department: 'Procurement & Logistics',
      required_date: '2026-09-28',
      purpose: 'Safety equipment replenishment for new glazing subcontract team.',
      urgency: 'medium',
      status: 'approved',
      created_at: '2026-09-23 14:15',
      items: [
        { item_name: 'Standard Industrial Hard Hat (ANSI Z89.1)', quantity: 20, unit: 'Pieces', estimated_cost: 90000 }
      ],
      total_amount: 90000
    },
    {
      id: 3,
      requisition_no: 'REQ-2026-0040',
      project_id: 2,
      project_name: 'Commercial Mixed-Use Plaza',
      requested_by_name: 'Engr. David Okon',
      requested_by_role: 'Site Engineer',
      department: 'Civil Engineering',
      required_date: '2026-09-20',
      purpose: 'Rebar placement for ground floor slab reinforcement.',
      urgency: 'high',
      status: 'dispatched',
      created_at: '2026-09-18 11:00',
      items: [
        { item_name: 'High-Yield Rebar 16mm TMT (12m length)', quantity: 60, unit: 'Lengths', estimated_cost: 852000 }
      ],
      total_amount: 852000
    }
  ],

  purchaseOrders: [
    {
      id: 1,
      po_number: 'PO-2026-0089',
      supplier_name: 'Dangote Cement PLC',
      project_name: 'Bridge Extension & Dualization',
      issue_date: '2026-09-20',
      expected_delivery: '2026-10-01',
      total_amount: 5250000,
      status: 'received',
      payment_status: 'paid',
      items: [
        { description: 'Dangote Falcon Cement 42.5R (50kg)', quantity: 500, unit_price: 10500, total: 5250000 }
      ]
    },
    {
      id: 2,
      po_number: 'PO-2026-0090',
      supplier_name: 'Tiger Steel Nigeria Ltd',
      project_name: 'Commercial Mixed-Use Plaza',
      issue_date: '2026-09-24',
      expected_delivery: '2026-10-05',
      total_amount: 14200000,
      status: 'pending_delivery',
      payment_status: 'partial',
      items: [
        { description: 'High-Yield Rebar 16mm TMT', quantity: 1000, unit_price: 14200, total: 14200000 }
      ]
    }
  ],

  chatMessages: [
    { id: 1, sender_id: 2, sender_name: 'Alhaji Ibrahim Usman (MD)', channel: 'general', text: 'Good morning team. Please ensure the weekly site audit report for the Bridge Extension is uploaded before 4 PM today.', timestamp: '08:45 AM' },
    { id: 2, sender_id: 3, sender_name: 'Engr. David Okon (Site Eng.)', channel: 'general', text: 'Good morning MD. The report is 90% compiled. Pier foundation testing records will be attached in the afternoon.', timestamp: '09:02 AM' },
    { id: 3, sender_id: 5, sender_name: 'Tunde Bakare (Procurement)', channel: 'general', text: 'Dangote cement delivery of 500 bags confirmed at Central Yard. Waybill signed.', timestamp: '10:15 AM' },
    { id: 4, sender_id: 4, sender_name: 'Grace Nwosu (Accounts)', channel: 'general', text: 'Payment batch for sub-contractor progress certificates has been prepared for executive approval.', timestamp: '11:30 AM' }
  ],

  departments: [
    { id: 1, name: 'Executive & Administration', head_name: 'Alhaji Ibrahim Usman', employee_count: 3 },
    { id: 2, name: 'Civil Engineering & Construction', head_name: 'Engr. David Okon', employee_count: 14 },
    { id: 3, name: 'Finance & Accounts', head_name: 'Grace Nwosu', employee_count: 4 },
    { id: 4, name: 'Procurement & Logistics', head_name: 'Tunde Bakare', employee_count: 6 },
    { id: 5, name: 'Human Resources', head_name: 'Fatima Bello', employee_count: 3 },
    { id: 6, name: 'Quality Assurance & Surveying', head_name: 'Engr. Paul Odey', employee_count: 5 }
  ],

  salaryAdvances: [
    { id: 1, employee_name: 'Musa Garba', department: 'Civil Engineering', amount: 150000, reason: 'Medical emergency', request_date: '2026-09-18', status: 'approved' },
    { id: 2, employee_name: 'Chidi Amadi', department: 'Procurement', amount: 80000, reason: 'Family relocation support', request_date: '2026-09-22', status: 'pending' }
  ],

  employeeLoans: [
    { id: 1, employee_name: 'David Okon', principal: 1200000, tenure_months: 12, monthly_deduction: 100000, balance: 600000, status: 'active', start_date: '2026-03-01' },
    { id: 2, employee_name: 'Grace Nwosu', principal: 800000, tenure_months: 8, monthly_deduction: 100000, balance: 200000, status: 'active', start_date: '2026-04-01' }
  ],

  documents: [
    {
      id: 1,
      company_id: 1,
      title: 'Environmental & Social Impact Assessment (ESIA) Clearance',
      category: 'Statutory Approvals & Permits',
      file_name: 'sample_esia_permit.pdf',
      original_name: 'ESIA_Federal_Clearance_Bridge_Ext.pdf',
      file_path: 'public/uploads/documents/sample_esia_permit.pdf',
      file_type: 'application/pdf',
      file_size: 1843200,
      related_type: 'project_enable',
      related_id: 1,
      uploaded_by: 2,
      uploaded_by_name: 'Alhaji Ibrahim Usman (MD)',
      created_at: '2025-08-10 11:20'
    },
    {
      id: 2,
      company_id: 1,
      title: 'Structural Engineering Approved For Construction (AFC) Blueprint',
      category: 'Engineering & Architectural Drawings',
      file_name: 'sample_afc_drawing.pdf',
      original_name: 'Bridge_Pier_Substructure_AFC_Rev2.pdf',
      file_path: 'public/uploads/documents/sample_afc_drawing.pdf',
      file_type: 'application/pdf',
      file_size: 4718592,
      related_type: 'project_enable',
      related_id: 1,
      uploaded_by: 3,
      uploaded_by_name: 'Engr. David Okon (Site Engineer)',
      created_at: '2025-08-15 14:45'
    },
    {
      id: 3,
      company_id: 1,
      title: 'Site Mobilization & Traffic Management Safety Plan',
      category: 'HSE & Site Safety Plans',
      file_name: 'sample_safety_plan.pdf',
      original_name: 'Traffic_Management_Site_Mobilization_V1.pdf',
      file_path: 'public/uploads/documents/sample_safety_plan.pdf',
      file_type: 'application/pdf',
      file_size: 838860,
      related_type: 'project_enable',
      related_id: 1,
      uploaded_by: 3,
      uploaded_by_name: 'Engr. David Okon (Site Engineer)',
      created_at: '2025-08-18 09:15'
    },
    {
      id: 4,
      company_id: 1,
      title: 'Commercial Plaza Geotechnical Soil Boring Report',
      category: 'Survey & Geotechnical Reports',
      file_name: 'sample_geotech_report.pdf',
      original_name: 'Plaza_Geotech_Investigation_Report.pdf',
      file_path: 'public/uploads/documents/sample_geotech_report.pdf',
      file_type: 'application/pdf',
      file_size: 2936012,
      related_type: 'project_enable',
      related_id: 2,
      uploaded_by: 2,
      uploaded_by_name: 'Alhaji Ibrahim Usman (MD)',
      created_at: '2025-10-22 16:30'
    }
  ]
};

// Helper methods for the store
export function getActiveCompany() {
  return store.companies.find(c => c.id === store.activeCompanyId) || store.companies[0];
}

export function setActiveCompany(id) {
  const company = store.companies.find(c => c.id === Number(id));
  if (company) {
    store.activeCompanyId = company.id;
    return true;
  }
  return false;
}

export function getProjectDocuments(projectId, companyId) {
  return store.documents.filter(d => 
    (!companyId || d.company_id === Number(companyId)) && 
    (!projectId || d.related_id === Number(projectId))
  );
}

export function getDocumentById(id, companyId) {
  return store.documents.find(d => 
    d.id === Number(id) && (!companyId || d.company_id === Number(companyId))
  );
}

export function addDocument(doc) {
  const nextId = store.documents.length ? Math.max(...store.documents.map(d => d.id)) + 1 : 1;
  const newDoc = {
    id: nextId,
    ...doc,
    created_at: doc.created_at || new Date().toISOString().replace('T', ' ').substring(0, 16)
  };
  store.documents.unshift(newDoc);
  return newDoc;
}

export function deleteDocument(id, companyId) {
  const index = store.documents.findIndex(d => 
    d.id === Number(id) && (!companyId || d.company_id === Number(companyId))
  );
  if (index !== -1) {
    const deleted = store.documents.splice(index, 1)[0];
    return deleted;
  }
  return null;
}
