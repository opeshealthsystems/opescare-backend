
content = r'''
<!-- ===== ADMIN PORTAL ===== -->
<div class="portal-pane" id="portal-admin">
  <div class="portal-wrap">
    <div class="portal-sidebar">
      <div class="sb-brand"><div class="sb-logo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div><div class="sb-name">OpesCare</div><div class="sb-sub">Admin Portal</div></div></div>
      <div class="sb-rb"><div class="role-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Super Admin</div></div>
      <nav class="sb-nav">
        <div class="sb-sec">Overview</div>
        <div class="sb-link active" id="adm-nav-dashboard" onclick="goPage('admin','dashboard')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</div>
        <div class="sb-sec">Platform</div>
        <div class="sb-link" id="adm-nav-facilities" onclick="goPage('admin','facilities')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Facilities</div>
        <div class="sb-link" id="adm-nav-organizations" onclick="goPage('admin','organizations')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Organizations</div>
        <div class="sb-link" id="adm-nav-users" onclick="goPage('admin','users')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Users</div>
        <div class="sb-link" id="adm-nav-roles" onclick="goPage('admin','roles')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Roles &amp; Permissions</div>
        <div class="sb-sec">Billing</div>
        <div class="sb-link" id="adm-nav-subscriptions" onclick="goPage('admin','subscriptions')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>Subscriptions</div>
        <div class="sb-link" id="adm-nav-financial" onclick="goPage('admin','financial')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Financial</div>
        <div class="sb-sec">Operations</div>
        <div class="sb-link" id="adm-nav-onboarding" onclick="goPage('admin','onboarding')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Onboarding</div>
        <div class="sb-link" id="adm-nav-support-tickets" onclick="goPage('admin','support-tickets')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/></svg>Support Tickets</div>
        <div class="sb-link" id="adm-nav-security-logs" onclick="goPage('admin','security-logs')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>Security Logs</div>
        <div class="sb-sec">System</div>
        <div class="sb-link" id="adm-nav-control-center" onclick="goPage('admin','control-center')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 1 21 12a10 10 0 0 1-1.93 5.81M16.95 7.05A7 7 0 0 1 19 12a7 7 0 0 1-2.05 4.95M5 12a7 7 0 0 1 7-7"/></svg>Control Center</div>
        <div class="sb-link" id="adm-nav-cdss-rules" onclick="goPage('admin','cdss-rules')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>CDSS Rules</div>
        <div class="sb-link" id="adm-nav-api-monitor" onclick="goPage('admin','api-monitor')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>API Monitor</div>
        <div class="sb-link" id="adm-nav-data-retention" onclick="goPage('admin','data-retention')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Data Retention</div>
      </nav>
      <div class="sb-foot"><div class="sb-user"><div class="sb-av">SA</div><div><div class="sb-uname">System Admin</div><div class="sb-urole">super_admin</div></div></div></div>
    </div>
    <div class="portal-main">
      <div class="portal-topbar">
        <span class="tb-crumb">Admin</span><span class="tb-sep">/</span>
        <span class="tb-page" id="adm-breadcrumb">Dashboard</span>
        <div class="tb-right">
          <div class="icon-btn"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
          <div class="tb-av">SA</div><span class="tb-uname">System Admin</span>
        </div>
      </div>
      <div class="portal-content">
        <!-- ADMIN DASHBOARD -->
        <div class="portal-page active" id="adm-page-dashboard">
          <div class="stat-grid" style="grid-template-columns:repeat(7,1fr)">
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div class="sc-val">247</div><div class="sc-lbl">Facilities</div></div>
            <div class="stat-card teal"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="sc-val">38</div><div class="sc-lbl">Organizations</div></div>
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/></svg></div><div class="sc-val">214</div><div class="sc-lbl">Active Subscriptions</div></div>
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="sc-val">1,842</div><div class="sc-lbl">Total Users</div></div>
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg></div><div class="sc-val">12</div><div class="sc-lbl">Pending Onboarding</div></div>
            <div class="stat-card danger"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/></svg></div><div class="sc-val">7</div><div class="sc-lbl">Open Tickets</div></div>
            <div class="stat-card info"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="sc-val">4.82M</div><div class="sc-lbl">Monthly Rev (XAF)</div></div>
          </div>
          <div class="two-col">
            <div class="panel">
              <div class="panel-hd"><span class="panel-title">Platform Health</span></div>
              <div class="panel-body">
                <div class="health-bar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>API: Operational · 99.8% uptime</div>
                <div class="health-bar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>Database (PostgreSQL): Healthy · 145ms avg</div>
                <div class="health-bar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>Redis Cache: Operational · 2ms avg</div>
                <div class="health-bar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>Queue (Horizon): Running · 0 failed</div>
              </div>
            </div>
            <div class="panel">
              <div class="panel-hd"><span class="panel-title">Recent Security Events</span></div>
              <div class="panel-body" style="padding:0">
                <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);font-size:12px"><span class="badge badge-danger" style="margin-right:8px">ALERT</span>Failed login × 5 — IP 196.203.14.22 <span style="color:var(--muted);float:right">10:14</span></div>
                <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);font-size:12px"><span class="badge badge-ok" style="margin-right:8px">OK</span>Facility onboarded — Clinique Ndokoti <span style="color:var(--muted);float:right">09:30</span></div>
                <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);font-size:12px"><span class="badge badge-warn" style="margin-right:8px">WARN</span>Rate limit hit — App DEV-00192 <span style="color:var(--muted);float:right">09:12</span></div>
                <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);font-size:12px"><span class="badge badge-ok" style="margin-right:8px">OK</span>New developer account verified <span style="color:var(--muted);float:right">08:55</span></div>
                <div style="padding:10px 14px;font-size:12px"><span class="badge badge-info" style="margin-right:8px">INFO</span>Break-glass access logged — CM-HID-7KQ9 <span style="color:var(--muted);float:right">08:30</span></div>
              </div>
            </div>
          </div>
          <div class="panel">
            <div class="panel-hd"><span class="panel-title">FHIR API Stats</span></div>
            <div class="panel-body" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;text-align:center">
              <div><div style="font-size:22px;font-weight:800;color:var(--b)">48,291</div><div style="font-size:12px;color:var(--muted)">Requests today</div></div>
              <div><div style="font-size:22px;font-weight:800;color:var(--ok)">99.9%</div><div style="font-size:12px;color:var(--muted)">Uptime 30d</div></div>
              <div><div style="font-size:22px;font-weight:800;color:var(--warn)">0.03%</div><div style="font-size:12px;color:var(--muted)">Error rate</div></div>
              <div><div style="font-size:22px;font-weight:800;color:var(--t)">CHU Yaoundé</div><div style="font-size:12px;color:var(--muted)">Top API consumer</div></div>
            </div>
          </div>
        </div>
        <!-- FACILITIES -->
        <div class="portal-page" id="adm-page-facilities">
          <div class="page-header"><div><h2>Facilities</h2><p>All registered healthcare facilities</p></div><div class="ph-actions"><button class="btn btn-secondary btn-sm">Export CSV</button><button class="btn btn-primary">Add Facility</button></div></div>
          <div class="filter-bar">
            <select class="form-control"><option>All Countries</option><option selected>Cameroon (CM)</option></select>
            <select class="form-control"><option>All Regions</option><option>Centre</option><option>Littoral</option><option>West</option></select>
            <select class="form-control"><option>All Types</option><option>Hospital</option><option>Clinic</option><option>Laboratory</option><option>Pharmacy</option></select>
            <select class="form-control"><option>All Statuses</option><option>Active</option><option>Suspended</option><option>Pending</option></select>
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Name</th><th>Type</th><th>Region</th><th>Country</th><th>Plan</th><th>Users</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><strong>CHU de Yaoundé</strong></td><td>University Hospital</td><td>Centre</td><td>CM</td><td><span class="badge badge-b">Enterprise</span></td><td>142</td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button> <button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>Hôpital Central Yaoundé</strong></td><td>General Hospital</td><td>Centre</td><td>CM</td><td><span class="badge badge-b">Enterprise</span></td><td>89</td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button> <button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>Clinique de la Paix Douala</strong></td><td>Private Clinic</td><td>Littoral</td><td>CM</td><td><span class="badge badge-info">Standard</span></td><td>28</td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button> <button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>Hôpital Régional Bafoussam</strong></td><td>Regional Hospital</td><td>West</td><td>CM</td><td><span class="badge badge-info">Standard</span></td><td>56</td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button> <button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>Clinique Ndokoti</strong></td><td>Clinic</td><td>Littoral</td><td>CM</td><td><span class="badge badge-muted">Lite</span></td><td>5</td><td><span class="badge badge-warn">Pending</span></td><td><button class="btn btn-secondary btn-xs">Review</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- ORGANIZATIONS -->
        <div class="portal-page" id="adm-page-organizations">
          <div class="page-header"><div><h2>Organizations</h2></div><button class="btn btn-primary">Add Organization</button></div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Name</th><th>Type</th><th>Facilities</th><th>Country</th><th>Plan</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><strong>Ministère de la Santé CM</strong></td><td>Government</td><td>247</td><td>CM</td><td><span class="badge badge-b">Enterprise</span></td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Réseau Santé Cameroun</strong></td><td>NGO / Health Org</td><td>38</td><td>CM</td><td><span class="badge badge-info">Standard</span></td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Activa Santé SA</strong></td><td>Insurance</td><td>12</td><td>CM</td><td><span class="badge badge-info">Standard</span></td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>PharmaCM Group</strong></td><td>Pharmacy Chain</td><td>6</td><td>CM</td><td><span class="badge badge-muted">Lite</span></td><td><span class="badge badge-warn">Trial</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- USERS -->
        <div class="portal-page" id="adm-page-users">
          <div class="page-header"><div><h2>Users</h2></div><div class="ph-actions"><button class="btn btn-secondary btn-sm">Import Users</button><button class="btn btn-primary">Invite User</button></div></div>
          <div class="filter-bar">
            <input class="form-control" placeholder="Search name or email..." style="width:220px">
            <select class="form-control"><option>All Roles</option><option>Doctor</option><option>Admin</option><option>Lab Tech</option><option>Pharmacist</option></select>
            <select class="form-control"><option>All Statuses</option><option>Active</option><option>Suspended</option></select>
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Facility</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><strong>Dr. Ndongo Emmanuel</strong></td><td>ndongo.e@chu-yaounde.cm</td><td><span class="badge badge-b">Doctor</span></td><td>CHU de Yaoundé</td><td><span class="badge badge-ok">Active</span></td><td>2025-06-14 10:05</td><td><button class="btn btn-secondary btn-xs">View</button> <button class="btn btn-xs" style="background:var(--warnl);color:var(--warn);border:1px solid rgba(180,83,9,.2)">Suspend</button></td></tr>
                <tr><td><strong>Dr. Fopa Isabelle</strong></td><td>fopa.i@chu-yaounde.cm</td><td><span class="badge badge-b">Doctor</span></td><td>CHU de Yaoundé</td><td><span class="badge badge-ok">Active</span></td><td>2025-06-14 08:12</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Mbarga Alain C.</strong></td><td>mbarga.alain@gmail.com</td><td><span class="badge badge-muted">Patient</span></td><td>—</td><td><span class="badge badge-ok">Active</span></td><td>2025-06-14 09:42</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Lab Tech Kouam</strong></td><td>kouam@chu-yaounde.cm</td><td><span class="badge badge-teal" style="background:rgba(15,118,110,.1);color:var(--t)">Lab Tech</span></td><td>CHU de Yaoundé</td><td><span class="badge badge-ok">Active</span></td><td>2025-06-14 07:58</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Pharmacien Nkeng</strong></td><td>nkeng@pharmcm.cm</td><td><span class="badge badge-warn">Pharmacist</span></td><td>Pharmacie Centrale</td><td><span class="badge badge-warn">Pending</span></td><td>Never</td><td><button class="btn btn-secondary btn-xs">Activate</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- ROLES -->
        <div class="portal-page" id="adm-page-roles">
          <div class="page-header"><div><h2>Roles &amp; Permissions</h2></div><button class="btn btn-primary">Create Role</button></div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Role Name</th><th>Category</th><th>Users</th><th>Description</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><strong>super_admin</strong></td><td><span class="badge badge-danger">Platform</span></td><td>3</td><td>Full platform access, system config</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>doctor</strong></td><td><span class="badge badge-b">Clinical</span></td><td>142</td><td>Patient records, visits, prescriptions, lab orders</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>lab_technician</strong></td><td><span class="badge badge-b">Clinical</span></td><td>48</td><td>Sample management, result entry, QC</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>pharmacist</strong></td><td><span class="badge badge-b">Clinical</span></td><td>64</td><td>Dispense prescriptions, inventory</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>facility_admin</strong></td><td><span class="badge badge-info">Admin</span></td><td>89</td><td>Manage facility users, billing, reports</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
              </tbody>
            </table>
          </div>
          <div class="panel">
            <div class="panel-hd"><span class="panel-title">Edit Role: doctor</span></div>
            <div class="panel-body">
              <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:8px">Patient Records</div>
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:16px">
                <div class="checkbox-item"><input type="checkbox" checked> read:patient</div>
                <div class="checkbox-item"><input type="checkbox" checked> write:patient</div>
                <div class="checkbox-item"><input type="checkbox" checked> read:lab_results</div>
                <div class="checkbox-item"><input type="checkbox" checked> write:prescriptions</div>
                <div class="checkbox-item"><input type="checkbox" checked> read:visits</div>
                <div class="checkbox-item"><input type="checkbox" checked> write:visits</div>
              </div>
              <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:8px">Administrative</div>
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:16px">
                <div class="checkbox-item"><input type="checkbox" checked> read:billing</div>
                <div class="checkbox-item"><input type="checkbox"> write:billing</div>
                <div class="checkbox-item"><input type="checkbox"> manage:users</div>
              </div>
              <button class="btn btn-primary">Save Permissions</button>
            </div>
          </div>
        </div>
        <!-- SUBSCRIPTIONS -->
        <div class="portal-page" id="adm-page-subscriptions">
          <div class="page-header"><div><h2>Subscriptions</h2></div></div>
          <div class="alert alert-warn"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg><div>3 subscriptions expiring within 7 days. <a style="color:var(--warn);font-weight:600;cursor:pointer">View renewals →</a></div></div>
          <div class="filter-bar">
            <select class="form-control"><option>All Plans</option><option>Lite</option><option>Standard</option><option>Enterprise</option></select>
            <select class="form-control"><option>All Statuses</option><option>Active</option><option>Expiring</option><option>Expired</option></select>
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Facility</th><th>Plan</th><th>Start</th><th>Expiry</th><th>Amount (XAF/mo)</th><th>Status</th><th>Auto-Renew</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><strong>CHU de Yaoundé</strong></td><td><span class="badge badge-b">Enterprise</span></td><td>01 Jan 2025</td><td>31 Dec 2025</td><td><strong>150,000</strong></td><td><span class="badge badge-ok">Active</span></td><td><span class="badge badge-ok">ON</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Clinique de la Paix</strong></td><td><span class="badge badge-info">Standard</span></td><td>15 Jan 2025</td><td>20 Jun 2025</td><td><strong>15,000</strong></td><td><span class="badge badge-danger">Expiring 6d</span></td><td><span class="badge badge-muted">OFF</span></td><td><button class="btn btn-primary btn-xs">Renew</button></td></tr>
                <tr><td><strong>Hôpital Régional Bafoussam</strong></td><td><span class="badge badge-info">Standard</span></td><td>01 Mar 2025</td><td>28 Feb 2026</td><td><strong>15,000</strong></td><td><span class="badge badge-ok">Active</span></td><td><span class="badge badge-ok">ON</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Centre Médical Yaoundé</strong></td><td><span class="badge badge-muted">Lite</span></td><td>01 Jun 2025</td><td>—</td><td><strong>0</strong></td><td><span class="badge badge-ok">Active</span></td><td>—</td><td><button class="btn btn-secondary btn-xs">Upgrade</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- FINANCIAL -->
        <div class="portal-page" id="adm-page-financial">
          <div class="page-header"><div><h2>Financial</h2></div><div class="ph-actions"><input class="form-control" type="date" style="width:150px"><input class="form-control" type="date" style="width:150px"></div></div>
          <div class="stat-grid cols-4">
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="sc-val">57,820,000</div><div class="sc-lbl">Total Revenue (XAF)</div></div>
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg></div><div class="sc-val">51,240,000</div><div class="sc-lbl">Collected (XAF)</div></div>
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg></div><div class="sc-val">5,980,000</div><div class="sc-lbl">Pending (XAF)</div></div>
            <div class="stat-card danger"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,3 21,3 21,9"/><path d="M21 3L9 15l-6-6"/></svg></div><div class="sc-val">600,000</div><div class="sc-lbl">Refunded (XAF)</div></div>
          </div>
          <div class="panel">
            <div class="panel-hd"><span class="panel-title">Revenue by Month (XAF, 2025)</span></div>
            <div class="panel-body">
              <div class="bar-chart" style="height:140px">
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">4.2M</div><div class="bar" style="height:56%"></div><div class="bar-lbl">Jan</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">3.8M</div><div class="bar" style="height:51%"></div><div class="bar-lbl">Feb</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">5.1M</div><div class="bar" style="height:68%"></div><div class="bar-lbl">Mar</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">4.7M</div><div class="bar" style="height:63%"></div><div class="bar-lbl">Apr</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">5.4M</div><div class="bar" style="height:72%"></div><div class="bar-lbl">May</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">4.82M</div><div class="bar" style="height:64%;background:var(--tl)"></div><div class="bar-lbl">Jun</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">—</div><div class="bar" style="height:4%;background:var(--bdr)"></div><div class="bar-lbl">Jul</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">—</div><div class="bar" style="height:4%;background:var(--bdr)"></div><div class="bar-lbl">Aug</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">—</div><div class="bar" style="height:4%;background:var(--bdr)"></div><div class="bar-lbl">Sep</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">—</div><div class="bar" style="height:4%;background:var(--bdr)"></div><div class="bar-lbl">Oct</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">—</div><div class="bar" style="height:4%;background:var(--bdr)"></div><div class="bar-lbl">Nov</div></div>
                <div class="bar-wrap"><div class="bar-val" style="font-size:9px">—</div><div class="bar" style="height:4%;background:var(--bdr)"></div><div class="bar-lbl">Dec</div></div>
              </div>
            </div>
          </div>
          <div class="panel">
            <div class="panel-hd"><span class="panel-title">Recent Transactions</span></div>
            <table class="data-table">
              <thead><tr><th>Date</th><th>Facility</th><th>Plan</th><th>Amount (XAF)</th><th>Method</th><th>Status</th></tr></thead>
              <tbody>
                <tr><td>14 Jun 2025</td><td>CHU de Yaoundé</td><td>Enterprise</td><td><strong>150,000</strong></td><td>MTN MoMo</td><td><span class="badge badge-ok">Settled</span></td></tr>
                <tr><td>13 Jun 2025</td><td>Hôpital Régional Bafoussam</td><td>Standard</td><td><strong>15,000</strong></td><td>Orange Money</td><td><span class="badge badge-ok">Settled</span></td></tr>
                <tr><td>12 Jun 2025</td><td>Clinique de la Paix Douala</td><td>Standard</td><td><strong>15,000</strong></td><td>MTN MoMo</td><td><span class="badge badge-warn">Pending</span></td></tr>
                <tr><td>11 Jun 2025</td><td>Polyclinique Bonanjo</td><td>Lite</td><td><strong>0</strong></td><td>—</td><td><span class="badge badge-muted">Free</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- ONBOARDING KANBAN -->
        <div class="portal-page" id="adm-page-onboarding">
          <div class="page-header"><div><h2>Facility Onboarding</h2></div></div>
          <div class="kanban">
            <div class="kanban-col">
              <div class="kanban-col-hd">Pending Review <span class="badge-count">4</span></div>
              <div class="kanban-card"><strong style="font-size:13px">Clinique Ndokoti</strong><p>Clinic · Littoral · Submitted 12 Jun</p><div style="margin-top:8px;display:flex;gap:6px"><button class="btn btn-primary btn-xs">Start Review</button></div></div>
              <div class="kanban-card"><strong style="font-size:13px">Lab Bioscience Garoua</strong><p>Laboratory · North · Submitted 13 Jun</p><div style="margin-top:8px;display:flex;gap:6px"><button class="btn btn-primary btn-xs">Start Review</button></div></div>
              <div class="kanban-card"><strong style="font-size:13px">Pharmacie du Peuple</strong><p>Pharmacy · West · Submitted 14 Jun</p><div style="margin-top:8px;display:flex;gap:6px"><button class="btn btn-primary btn-xs">Start Review</button></div></div>
              <div class="kanban-card"><strong style="font-size:13px">Centre de Santé Ebolowa</strong><p>Health Center · South · Submitted 14 Jun</p><div style="margin-top:8px;display:flex;gap:6px"><button class="btn btn-primary btn-xs">Start Review</button></div></div>
            </div>
            <div class="kanban-col">
              <div class="kanban-col-hd">Documents Requested <span class="badge-count" style="background:var(--warn)">2</span></div>
              <div class="kanban-card"><strong style="font-size:13px">Hôpital Bethel Bamenda</strong><p>Hospital · NW · Reviewer: Admin Fouda</p><p style="color:var(--warn)">⚠ Awaiting license copy</p></div>
              <div class="kanban-card"><strong style="font-size:13px">Clinique Saint-Luc</strong><p>Clinic · Centre · Reviewer: Admin Biya</p><p style="color:var(--warn)">⚠ Awaiting accreditation</p></div>
            </div>
            <div class="kanban-col">
              <div class="kanban-col-hd">Approved <span class="badge-count" style="background:var(--ok)">3</span></div>
              <div class="kanban-card"><strong style="font-size:13px">Polyclinique Bonanjo</strong><p>Polyclinic · Littoral · Approved 10 Jun</p><span class="badge badge-ok">Approved</span></div>
              <div class="kanban-card"><strong style="font-size:13px">Pharmacie Médicale Yaoundé</strong><p>Pharmacy · Centre · Approved 09 Jun</p><span class="badge badge-ok">Approved</span></div>
              <div class="kanban-card"><strong style="font-size:13px">Lab Analyses Douala</strong><p>Laboratory · Littoral · Approved 08 Jun</p><span class="badge badge-ok">Approved</span></div>
            </div>
            <div class="kanban-col">
              <div class="kanban-col-hd">Active <span class="badge-count" style="background:var(--b)">247</span></div>
              <div class="kanban-card"><strong style="font-size:13px">CHU de Yaoundé</strong><p>University Hospital · Active since 2023</p><span class="badge badge-b">Enterprise</span></div>
              <div class="kanban-card"><strong style="font-size:13px">Hôpital Central Yaoundé</strong><p>General Hospital · Active since 2023</p><span class="badge badge-b">Enterprise</span></div>
              <div style style="font-size:12px;color:var(--muted);text-align:center;padding:8px">+ 245 more...</div>
            </div>
          </div>
        </div>
        <!-- SUPPORT TICKETS -->
        <div class="portal-page" id="adm-page-support-tickets">
          <div class="page-header"><div><h2>Support Tickets</h2></div></div>
          <div class="filter-bar">
            <select class="form-control"><option>All Priorities</option><option>Critical</option><option>High</option><option>Medium</option><option>Low</option></select>
            <select class="form-control"><option>All Statuses</option><option>Open</option><option>In Progress</option><option>Resolved</option></select>
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Ticket #</th><th>Facility</th><th>Subject</th><th>Priority</th><th>Status</th><th>Assignee</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td class="mono">TKT-4901</td><td>CHU de Yaoundé</td><td>Telemedicine audio drops</td><td><span class="badge badge-warn">High</span></td><td><span class="badge badge-warn">Open</span></td><td>Admin Fouda</td><td>13 Jun</td><td><button class="btn btn-secondary btn-xs">View</button> <button class="btn btn-secondary btn-xs">Close</button></td></tr>
                <tr><td class="mono">TKT-4892</td><td>Clinique de la Paix</td><td>Login failure after password reset</td><td><span class="badge badge-danger">Critical</span></td><td><span class="badge badge-info">In Progress</span></td><td>Admin Biya</td><td>12 Jun</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td class="mono">TKT-4855</td><td>Hôpital Régional</td><td>Report export stuck</td><td><span class="badge badge-muted">Medium</span></td><td><span class="badge badge-ok">Resolved</span></td><td>Admin Fouda</td><td>10 Jun</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- SECURITY LOGS -->
        <div class="portal-page" id="adm-page-security-logs">
          <div class="page-header"><div><h2>Security Logs</h2></div><button class="btn btn-secondary btn-sm">Export</button></div>
          <div class="filter-bar">
            <select class="form-control"><option>All Events</option><option>Login</option><option>Access</option><option>Break-glass</option><option>Rate Limit</option></select>
            <input class="form-control" type="date" style="width:150px">
            <select class="form-control"><option>All Results</option><option>Granted</option><option>Denied</option></select>
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Timestamp</th><th>Event</th><th>Health ID</th><th>Actor</th><th>Role</th><th>IP</th><th>Facility</th><th>Result</th></tr></thead>
              <tbody>
                <tr><td class="mono" style="font-size:11px">2025-06-14 10:14:22</td><td>Login Failed × 5</td><td>—</td><td>unknown</td><td>—</td><td class="mono">196.203.14.22</td><td>External</td><td><span class="badge badge-danger">Blocked</span></td></tr>
                <tr><td class="mono" style="font-size:11px">2025-06-14 10:05:11</td><td>Patient Access</td><td class="mono">CM-HID-7KQ9...</td><td>Dr. Ndongo</td><td>Doctor</td><td class="mono">10.0.1.45</td><td>CHU Yaoundé</td><td><span class="badge badge-ok">Granted</span></td></tr>
                <tr><td class="mono" style="font-size:11px">2025-06-14 09:58:04</td><td>Break-glass Access</td><td class="mono">CM-HID-3RT8...</td><td>Dr. Essomba</td><td>Doctor</td><td class="mono">10.0.1.52</td><td>CHU Yaoundé</td><td><span class="badge badge-warn">Logged</span></td></tr>
                <tr><td class="mono" style="font-size:11px">2025-06-14 09:12:00</td><td>Rate Limit Hit</td><td>—</td><td>DEV-00192</td><td>Developer</td><td class="mono">197.210.85.4</td><td>—</td><td><span class="badge badge-warn">Limited</span></td></tr>
                <tr><td class="mono" style="font-size:11px">2025-06-14 08:30:01</td><td>Patient Access Denied</td><td class="mono">CM-HID-8SS2...</td><td>external_app</td><td>—</td><td class="mono">41.209.10.1</td><td>External</td><td><span class="badge badge-danger">Denied</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- CONTROL CENTER -->
        <div class="portal-page" id="adm-page-control-center">
          <div class="page-header"><div><h2>Control Center</h2></div></div>
          <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
            <div class="stat-card ok"><div style="display:flex;align-items:center;justify-content:space-between"><div><div class="sc-val">API</div><div class="sc-lbl">99.8% uptime</div></div><span class="badge badge-ok" style="font-size:13px;padding:4px 12px">Healthy</span></div><div class="progress" style="margin-top:10px"><div class="progress-fill ok" style="width:99.8%"></div></div></div>
            <div class="stat-card ok"><div style="display:flex;align-items:center;justify-content:space-between"><div><div class="sc-val">Database</div><div class="sc-lbl">145ms avg</div></div><span class="badge badge-ok" style="font-size:13px;padding:4px 12px">Healthy</span></div><div class="progress" style="margin-top:10px"><div class="progress-fill ok" style="width:98%"></div></div></div>
            <div class="stat-card ok"><div style="display:flex;align-items:center;justify-content:space-between"><div><div class="sc-val">Redis</div><div class="sc-lbl">2ms avg</div></div><span class="badge badge-ok" style="font-size:13px;padding:4px 12px">Healthy</span></div><div class="progress" style="margin-top:10px"><div class="progress-fill ok" style="width:100%"></div></div></div>
            <div class="stat-card ok"><div style="display:flex;align-items:center;justify-content:space-between"><div><div class="sc-val">Queue</div><div class="sc-lbl">0 failed jobs</div></div><span class="badge badge-ok" style="font-size:13px;padding:4px 12px">Healthy</span></div><div class="progress" style="margin-top:10px"><div class="progress-fill ok" style="width:100%"></div></div></div>
            <div class="stat-card ok"><div style="display:flex;align-items:center;justify-content:space-between"><div><div class="sc-val">Storage</div><div class="sc-lbl">2.4 TB / 10 TB</div></div><span class="badge badge-ok" style="font-size:13px;padding:4px 12px">OK</span></div><div class="progress" style="margin-top:10px"><div class="progress-fill" style="width:24%"></div></div></div>
            <div class="stat-card ok"><div style="display:flex;align-items:center;justify-content:space-between"><div><div class="sc-val">Email</div><div class="sc-lbl">Postmark · 99.6%</div></div><span class="badge badge-ok" style="font-size:13px;padding:4px 12px">Healthy</span></div><div class="progress" style="margin-top:10px"><div class="progress-fill ok" style="width:99.6%"></div></div></div>
          </div>
          <div class="panel">
            <div class="panel-hd"><span class="panel-title">Feature Flags</span></div>
            <table class="data-table">
              <thead><tr><th>Flag</th><th>Description</th><th>Status</th><th>Toggle</th></tr></thead>
              <tbody>
                <tr><td class="mono">FHIR_R4_ENABLED</td><td>Enable FHIR R4 API endpoints</td><td><span class="badge badge-ok">ON</span></td><td><div class="toggle on"></div></td></tr>
                <tr><td class="mono">TELEMEDICINE_BETA</td><td>Telemedicine module (beta)</td><td><span class="badge badge-ok">ON</span></td><td><div class="toggle on"></div></td></tr>
                <tr><td class="mono">CDSS_AI_ALERTS</td><td>AI-powered clinical decision support</td><td><span class="badge badge-ok">ON</span></td><td><div class="toggle on"></div></td></tr>
                <tr><td class="mono">MAINTENANCE_MODE</td><td>Show maintenance page to all users</td><td><span class="badge badge-muted">OFF</span></td><td><div class="toggle"></div></td></tr>
                <tr><td class="mono">DEV_SANDBOX_RESET</td><td>Allow developers to reset sandbox</td><td><span class="badge badge-ok">ON</span></td><td><div class="toggle on"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- CDSS RULES -->
        <div class="portal-page" id="adm-page-cdss-rules">
          <div class="page-header"><div><h2>CDSS Rules</h2></div><button class="btn btn-primary">Create Rule</button></div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Rule Name</th><th>Condition</th><th>Action</th><th>Severity</th><th>Active</th><th>Last Triggered</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><strong>Drug Allergy Check</strong></td><td>IF prescribed_drug IN patient.allergies</td><td>ALERT: Critical — Allergy Conflict</td><td><span class="badge badge-danger">Critical</span></td><td><span class="badge badge-ok">ON</span></td><td>14 Jun 10:09</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>HbA1c Threshold</strong></td><td>IF HbA1c > 8.5 AND diagnosis = E11</td><td>WARN: Consider intensification</td><td><span class="badge badge-warn">Warning</span></td><td><span class="badge badge-ok">ON</span></td><td>12 Jun 14:22</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>Vaccination Overdue</strong></td><td>IF immunization.next_due < today</td><td>INFO: Schedule booster</td><td><span class="badge badge-info">Info</span></td><td><span class="badge badge-ok">ON</span></td><td>14 Jun 09:30</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
                <tr><td><strong>Critical BP Alert</strong></td><td>IF systolic > 180 OR diastolic > 110</td><td>ALERT: Critical — Hypertensive crisis</td><td><span class="badge badge-danger">Critical</span></td><td><span class="badge badge-ok">ON</span></td><td>13 Jun 11:45</td><td><button class="btn btn-secondary btn-xs">Edit</button></td></tr>
              </tbody>
            </table>
          </div>
          <div class="panel">
            <div class="panel-hd"><span class="panel-title">Create Rule</span></div>
            <div class="panel-body">
              <div class="form-group"><label class="form-label">Rule Name</label><input class="form-control" placeholder="e.g. Drug Interaction Check"></div>
              <div style="background:var(--sur2);border:1px solid var(--bdr);border-radius:var(--rad);padding:14px;margin-bottom:14px">
                <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:10px">CONDITION BUILDER</div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                  <span style="font-size:13px;font-weight:600;color:var(--b)">IF</span>
                  <select class="form-control" style="width:160px"><option>prescribed_drug</option><option>HbA1c</option><option>blood_pressure</option><option>allergy_list</option></select>
                  <select class="form-control" style="width:120px"><option>IN</option><option>></option><option>&lt;</option><option>=</option><option>!=</option></select>
                  <input class="form-control" style="width:160px" placeholder="value or list">
                  <span style="font-size:13px;font-weight:600;color:var(--b)">THEN</span>
                  <select class="form-control" style="width:200px"><option>ALERT (Critical)</option><option>WARN</option><option>INFO</option><option>BLOCK action</option></select>
                </div>
              </div>
              <div class="form-row cols-2">
                <div class="form-group"><label class="form-label">Severity</label><select class="form-control"><option>Critical</option><option>Warning</option><option>Info</option></select></div>
                <div class="form-group"><label class="form-label">Alert Message Template</label><input class="form-control" placeholder="Message shown to clinician..."></div>
              </div>
              <button class="btn btn-primary">Save Rule</button>
            </div>
          </div>
        </div>
        <!-- API MONITOR -->
        <div class="portal-page" id="adm-page-api-monitor">
          <div class="page-header"><div><h2>API Monitor</h2></div></div>
          <div class="stat-grid cols-4">
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg></div><div class="sc-val">48,291</div><div class="sc-lbl">Requests today</div></div>
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/></svg></div><div class="sc-val">99.97%</div><div class="sc-lbl">Success rate</div></div>
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div class="sc-val">0.03%</div><div class="sc-lbl">Error rate</div></div>
            <div class="stat-card teal"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div class="sc-val">187ms</div><div class="sc-lbl">P99 Latency</div></div>
          </div>
          <div class="panel">
            <div class="panel-hd"><span class="panel-title">Endpoint Breakdown</span></div>
            <table class="data-table">
              <thead><tr><th>Endpoint</th><th>Method</th><th>Calls</th><th>Errors</th><th>Avg Latency</th><th>P99</th></tr></thead>
              <tbody>
                <tr><td class="mono">/api/v1/fhir/Patient</td><td><span class="badge badge-ok">GET</span></td><td>18,429</td><td>3</td><td>42ms</td><td>145ms</td></tr>
                <tr><td class="mono">/api/v1/fhir/Observation</td><td><span class="badge badge-ok">GET</span></td><td>9,821</td><td>1</td><td>55ms</td><td>187ms</td></tr>
                <tr><td class="mono">/api/v1/appointments</td><td><span class="badge badge-b">POST</span></td><td>4,202</td><td>0</td><td>89ms</td><td>201ms</td></tr>
                <tr><td class="mono">/api/v1/lab-orders</td><td><span class="badge badge-b">POST</span></td><td>3,104</td><td>8</td><td>102ms</td><td>280ms</td></tr>
                <tr><td class="mono">/api/v1/verify/health-id</td><td><span class="badge badge-ok">GET</span></td><td>12,735</td><td>2</td><td>28ms</td><td>98ms</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- DATA RETENTION -->
        <div class="portal-page" id="adm-page-data-retention">
          <div class="page-header"><div><h2>Data Retention</h2><p>GDPR & Cameroonian health data law compliance</p></div></div>
          <div class="panel">
            <div class="panel-hd"><span class="panel-title">Retention Policies</span></div>
            <table class="data-table">
              <thead><tr><th>Data Type</th><th>Retention Period</th><th>Legal Basis</th><th>Last Run</th><th>Next Run</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><strong>Patient Records</strong></td><td><input class="form-control" style="width:80px;display:inline" value="10"> years</td><td>Cameroonian Health Law 2018</td><td>01 Jun 2025</td><td>01 Jul 2025</td><td><button class="btn btn-secondary btn-xs">Run Now</button></td></tr>
                <tr><td><strong>Audit Logs</strong></td><td><input class="form-control" style="width:80px;display:inline" value="7"> years</td><td>GDPR Art. 17 / Local Reg.</td><td>01 Jun 2025</td><td>01 Jul 2025</td><td><button class="btn btn-secondary btn-xs">Run Now</button></td></tr>
                <tr><td><strong>Financial Records</strong></td><td><input class="form-control" style="width:80px;display:inline" value="5"> years</td><td>OHADA Accounting Standards</td><td>01 Jun 2025</td><td>01 Jul 2025</td><td><button class="btn btn-secondary btn-xs">Run Now</button></td></tr>
                <tr><td><strong>Session Logs</strong></td><td><input class="form-control" style="width:80px;display:inline" value="90"> days</td><td>Security Policy</td><td>01 Jun 2025</td><td>15 Jun 2025</td><td><button class="btn btn-secondary btn-xs">Run Now</button></td></tr>
                <tr><td><strong>Temp Files</strong></td><td><input class="form-control" style="width:80px;display:inline" value="30"> days</td><td>Internal Policy</td><td>07 Jun 2025</td><td>07 Jul 2025</td><td><button class="btn btn-secondary btn-xs">Run Now</button></td></tr>
              </tbody>
            </table>
            <div style="padding:14px;text-align:right"><button class="btn btn-primary">Save All Policies</button></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
'''

with open(r'C:\laragon\www\opescare\apps\api-laravel\public\theme-preview\parts\admin.html', 'w', encoding='utf-8') as f:
    f.write(content)
print("Admin written:", len(content), "chars")
