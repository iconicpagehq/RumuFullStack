<?php
/**
 * Product-interface previews rendered in HTML/CSS.
 * These represent the shipped web panel with sample data; no customer logos,
 * no invented metrics, and no claims of a shipped Android app.
 */

function mockup_topbar(string $eyebrow, string $title, string $avatar = 'RS', string $chip = 'Nov 2026'): string
{
    return '<div class="app-topbar"><div><p class="app-eyebrow">' . e($eyebrow) . '</p><h3>' . e($title) . '</h3></div>'
        . '<div class="app-top-actions"><span class="app-chip">' . e($chip) . '</span><span class="app-avatar">' . e($avatar) . '</span></div></div>';
}

function mockup_shell(string $url, array $nav, string $active, string $content, string $user = 'Rohit S.', string $role = 'Realtor', string $class = ''): string
{
    $navHtml = '';
    foreach ($nav as $item) {
        $isActive = $item === $active;
        $navHtml .= '<span class="app-nav-item' . ($isActive ? ' is-active' : '') . '"><i class="app-nav-dot"></i>' . e($item) . '</span>';
    }
    return '<div class="app-window ' . e($class) . '">'
        . '<div class="app-chrome"><span class="app-dots"><i></i><i></i><i></i></span><span class="app-address">' . e($url) . '</span><span class="app-chrome-end"></span></div>'
        . '<div class="app-shell">'
        . '<aside class="app-sidebar"><div class="app-side-brand"><span class="app-side-mark"></span>Rumu</div><div class="app-nav">' . $navHtml . '</div>'
        . '<div class="app-user"><span class="app-avatar app-avatar--sm">' . e(substr($user, 0, 2)) . '</span><span><strong>' . e($user) . '</strong><small>' . e($role) . '</small></span></div></aside>'
        . '<div class="app-main">' . $content . '</div>'
        . '</div></div>';
}

function mockup_dashboard(bool $enterprise = false): string
{
    $nav = $enterprise
        ? ['Dashboard', 'Leads', 'Teams', 'Distribution', 'Targets', 'Reports', 'Inventory', 'Calls', 'Audit']
        : ['Dashboard', 'Lead Board', 'Campaigns', 'Customers', 'Inventory', 'Tasks', 'Calls', 'Billing'];
    $role = $enterprise ? 'Admin' : 'Realtor';
    $user = $enterprise ? 'Priya M.' : 'Rohit S.';
    $eyebrow = $enterprise ? 'Enterprise workspace' : 'Realtor workspace';

    $content = mockup_topbar($eyebrow, 'Dashboard', $enterprise ? 'PM' : 'RS');
    if ($enterprise) {
        $content .= '<div class="app-kpis">'
            . '<div class="app-kpi"><span>Active leads</span><strong>1,284</strong><small class="up">Assigned across 4 teams</small></div>'
            . '<div class="app-kpi"><span>Team conversion</span><strong>18.4%</strong><small>New → Won this month</small></div>'
            . '<div class="app-kpi"><span>Calls today</span><strong>96</strong><small>42 recordings available</small></div>'
            . '<div class="app-kpi"><span>Overdue follow-ups</span><strong>23</strong><small class="warn">Needs attention</small></div>'
            . '</div>';
        $content .= '<div class="app-columns"><div class="app-panel"><div class="app-panel-head"><h4>Team performance</h4><span class="app-link">This month</span></div>'
            . '<div class="app-table">'
            . '<div class="app-table-row app-table-head"><span>Team</span><span>Leads</span><span>Won</span><span>Target</span></div>'
            . '<div class="app-table-row"><span>Pune West</span><span>412</span><span>38</span><span><b class="bar-inline bar-92"></b></span></div>'
            . '<div class="app-table-row"><span>Bengaluru South</span><span>356</span><span>29</span><span><b class="bar-inline bar-74"></b></span></div>'
            . '<div class="app-table-row"><span>Mumbai Thane</span><span>301</span><span>24</span><span><b class="bar-inline bar-61"></b></span></div>'
            . '<div class="app-table-row"><span>Nagpur Central</span><span>215</span><span>11</span><span><b class="bar-inline bar-34"></b></span></div>'
            . '</div></div>';
        $content .= '<div class="app-panel"><div class="app-panel-head"><h4>Unassigned leads</h4><span class="app-link">Distribute</span></div>'
            . '<div class="app-list">'
            . '<div class="app-list-row"><span class="mini-avatar">AK</span><span><strong>Amit K.</strong><small>Skyline Residency · Meta</small></span><span class="app-tag">New</span></div>'
            . '<div class="app-list-row"><span class="mini-avatar">SN</span><span><strong>Sneha N.</strong><small>Palm Grove · Portal</small></span><span class="app-tag">New</span></div>'
            . '<div class="app-list-row"><span class="mini-avatar">VR</span><span><strong>Vikram R.</strong><small>Riverfront · Meta</small></span><span class="app-tag">New</span></div>'
            . '</div></div></div>';
    } else {
        $content .= '<div class="app-kpis">'
            . '<div class="app-kpi"><span>New inquiries</span><strong>38</strong><small class="up">+12 this week</small></div>'
            . '<div class="app-kpi"><span>Follow-ups today</span><strong>9</strong><small class="warn">2 overdue</small></div>'
            . '<div class="app-kpi"><span>Site visits</span><strong>6</strong><small>3 confirmed</small></div>'
            . '<div class="app-kpi"><span>Won deals</span><strong>4</strong><small>₹2.4 Cr value</small></div>'
            . '</div>';
        $content .= '<div class="app-columns"><div class="app-panel"><div class="app-panel-head"><h4>Conversion funnel</h4><span class="app-link">Campaigns</span></div>'
            . '<div class="funnel">'
            . '<div class="funnel-row"><span>New</span><b class="bar bar-100"></b><em>248</em></div>'
            . '<div class="funnel-row"><span>Contacted</span><b class="bar bar-76"></b><em>189</em></div>'
            . '<div class="funnel-row"><span>Qualified</span><b class="bar bar-52"></b><em>129</em></div>'
            . '<div class="funnel-row"><span>Site visit</span><b class="bar bar-31"></b><em>77</em></div>'
            . '<div class="funnel-row"><span>Won</span><b class="bar bar-14"></b><em>34</em></div>'
            . '</div></div>';
        $content .= '<div class="app-panel"><div class="app-panel-head"><h4>Upcoming follow-ups</h4><span class="app-link">All tasks</span></div>'
            . '<div class="app-list">'
            . '<div class="app-list-row"><span class="mini-avatar">MK</span><span><strong>Manish K.</strong><small>Skyline · 2BHK · ₹82L</small></span><span class="app-tag">Today</span></div>'
            . '<div class="app-list-row"><span class="mini-avatar">RS</span><span><strong>Riya S.</strong><small>Palm Grove · 3BHK · ₹1.1 Cr</small></span><span class="app-tag warn">Overdue</span></div>'
            . '<div class="app-list-row"><span class="mini-avatar">DP</span><span><strong>Deepak P.</strong><small>Riverfront · site visit</small></span><span class="app-tag">Tomorrow</span></div>'
            . '</div></div></div>';
    }

    return mockup_shell(
        $enterprise ? 'app.rumu.in/enterprise/dashboard' : 'app.rumu.in/realtor/dashboard',
        $nav,
        'Dashboard',
        $content,
        $user,
        $role
    );
}

function mockup_lead_board(): string
{
    $columns = [
        ['New', '18', [
            ['Amit K.', 'Skyline Residency', 'Meta', '₹82L', 'AK'],
            ['Sneha N.', 'Palm Grove', 'CSV import', '₹64L', 'SN'],
        ]],
        ['Contacted', '26', [
            ['Vikram R.', 'Riverfront', 'Meta', '₹1.2 Cr', 'VR'],
            ['Neha J.', 'Skyline Residency', 'Manual', '₹91L', 'NJ'],
        ]],
        ['Site visit', '11', [
            ['Manish K.', 'Palm Grove', 'Meta', '₹1.1 Cr', 'MK'],
            ['Aditya B.', 'Riverfront', 'Portal', '₹75L', 'AB'],
        ]],
        ['Negotiation', '7', [
            ['Riya S.', 'Skyline Residency', 'Meta', '₹1.4 Cr', 'RS'],
            ['Kabir M.', 'Palm Grove', 'Manual', '₹88L', 'KM'],
        ]],
    ];
    $board = '<div class="kb">';
    foreach ($columns as [$name, $count, $cards]) {
        $board .= '<div class="kb-col"><div class="kb-head"><span>' . e($name) . '</span><em>' . e($count) . '</em></div>';
        foreach ($cards as [$person, $project, $source, $value, $initials]) {
            $board .= '<div class="kb-card"><strong>' . e($person) . '</strong><small>' . e($project) . '</small>'
                . '<div class="kb-meta"><span class="app-tag">' . e($source) . '</span><b>' . e($value) . '</b></div>'
                . '<div class="kb-foot"><span class="mini-avatar">' . e($initials) . '</span><span>Next: today</span></div></div>';
        }
        $board .= '</div>';
    }
    $board .= '</div>';

    $content = mockup_topbar('Realtor workspace', 'Global lead board', 'RS', 'All campaigns')
        . '<div class="app-toolbar"><span class="app-chip">Kanban</span><span class="app-chip">Table</span><span class="app-chip">Search</span><span class="app-chip">Export CSV</span></div>'
        . $board;

    return mockup_shell('app.rumu.in/realtor/leads', ['Dashboard', 'Lead Board', 'Campaigns', 'Customers', 'Inventory', 'Tasks', 'Calls', 'Billing'], 'Lead Board', $content);
}

function mockup_lead_detail(bool $callsTab = false): string
{
    $tiles = '<div class="ld-tiles"><span class="ld-tile ld-tile--wa">WhatsApp</span><span class="ld-tile ld-tile--note">Note</span><span class="ld-tile ld-tile--act">Activity</span></div>';
    $profile = '<aside class="ld-profile"><span class="ld-avatar">RS</span><h4>Riya S.</h4><p>+91 98••• ••210</p>'
        . '<dl><div><dt>Source</dt><dd>Meta Lead Ads</dd></div><div><dt>Campaign</dt><dd>Skyline · 3BHK</dd></div><div><dt>Owner</dt><dd>Rohit S.</dd></div><div><dt>Deal value</dt><dd>₹1.4 Cr</dd></div></dl>'
        . '<div class="ld-actions"><span>Convert to customer</span><span>Delete lead</span></div></aside>';

    if ($callsTab) {
        $center = $tiles
            . '<div class="ld-tabs"><span>Activity</span><span>Notes</span><span>Follow-ups</span><span class="is-active">Calls</span><span>Status audit</span></div>'
            . '<div class="ld-call-card"><div class="call-row-head"><strong>Outgoing call</strong><span class="app-tag ok">Connected</span><span class="call-time">12:42</span></div>'
            . '<div class="call-player"><span class="play-btn">▶</span><span class="wave">' . str_repeat('<i></i>', 42) . '</span><span class="call-duration">02:18</span></div>'
            . '<p class="call-meta">Synced from Android · stored privately · playback logged</p></div>'
            . '<div class="ld-timeline"><div class="ld-event"><span class="ld-dot"></span><div><strong>Stage moved to Negotiation</strong><small>Today, 12:48 · Rohit S.</small><p>Buyer asked for floor-rise and parking options.</p></div></div>'
            . '<div class="ld-event"><span class="ld-dot"></span><div><strong>Site visit completed</strong><small>Yesterday, 5:10 PM · Tower B, Unit B-1204</small></div></div>'
            . '<div class="ld-event"><span class="ld-dot"></span><div><strong>Lead received from Meta</strong><small>28 Oct, 9:14 AM · Skyline · 3BHK form</small></div></div></div>';
    } else {
        $center = $tiles
            . '<div class="ld-tabs"><span class="is-active">Activity</span><span>Notes</span><span>Follow-ups</span><span>Calls</span><span>Status audit</span></div>'
            . '<div class="ld-timeline"><div class="ld-event"><span class="ld-dot"></span><div><strong>Note added</strong><small>Today, 3:04 PM · Rohit S.</small><p>Prefers a higher floor; budget flexible by ₹4–5L.</p></div></div>'
            . '<div class="ld-event"><span class="ld-dot"></span><div><strong>Follow-up scheduled</strong><small>Tomorrow, 11:00 AM · Call</small></div></div>'
            . '<div class="ld-event"><span class="ld-dot"></span><div><strong>Stage moved to Negotiation</strong><small>Today, 12:48 PM · Rohit S.</small></div></div>'
            . '<div class="ld-event"><span class="ld-dot"></span><div><strong>Lead received from Meta</strong><small>28 Oct, 9:14 AM · Skyline · 3BHK form</small></div></div></div>';
    }

    $stage = '<aside class="ld-stage"><p class="app-eyebrow">Stage</p><div class="stage-rail">'
        . '<span class="is-done">New</span><span class="is-done">Contacted</span><span class="is-done">Qualified</span><span class="is-current">Negotiation</span><span>Won</span><span>Lost</span>'
        . '</div><div class="ld-next"><p>Next follow-up</p><strong>Tomorrow · 11:00 AM</strong><span>Call · owner Rohit</span></div></aside>';

    $content = mockup_topbar('Realtor workspace', 'Lead detail', 'RS')
        . '<div class="ld">' . $profile . '<div class="ld-main">' . $center . '</div>' . $stage . '</div>';

    return mockup_shell($callsTab ? 'app.rumu.in/realtor/leads/riya-s?tab=calls' : 'app.rumu.in/realtor/leads/riya-s', ['Dashboard', 'Lead Board', 'Campaigns', 'Customers', 'Inventory', 'Tasks', 'Calls', 'Billing'], 'Lead Board', $content);
}

function mockup_calls(): string
{
    $rows = [
        ['Riya S.', '+91 98••• ••210', 'Connected', '02:18', true],
        ['Manish K.', '+91 90••• ••551', 'Interested', '01:42', true],
        ['Aditya B.', '+91 88••• ••904', 'Call back', '00:37', true],
        ['Sneha N.', '+91 70••• ••188', 'Not connected', '—', false],
    ];
    $list = '';
    foreach ($rows as [$name, $phone, $outcome, $duration, $hasRecording]) {
        $list .= '<div class="call-row"><span class="mini-avatar">' . e(substr($name, 0, 2)) . '</span>'
            . '<span class="call-person"><strong>' . e($name) . '</strong><small>' . e($phone) . '</small></span>'
            . '<span class="app-tag ' . ($outcome === 'Connected' || $outcome === 'Interested' ? 'ok' : '') . '">' . e($outcome) . '</span>'
            . '<span class="call-duration">' . e($duration) . '</span>'
            . ($hasRecording ? '<span class="call-play">▶</span>' : '<span class="call-play is-muted">—</span>')
            . '</div>';
    }

    $content = mockup_topbar('Realtor workspace', 'Calls & recordings', 'RS')
        . '<div class="app-kpis app-kpis--three">'
        . '<div class="app-kpi"><span>Total calls</span><strong>412</strong><small>Last 30 days</small></div>'
        . '<div class="app-kpi"><span>Connected</span><strong>268</strong><small>65% of dials</small></div>'
        . '<div class="app-kpi"><span>Recordings</span><strong>191</strong><small>Private storage</small></div>'
        . '</div>'
        . '<div class="app-panel"><div class="app-panel-head"><h4>Recent calls</h4><span class="app-link">Upload recording</span></div><div class="call-list">' . $list . '</div></div>'
        . '<div class="call-now"><span class="play-btn">▶</span><span><strong>Riya S. · Negotiation</strong><small>Recording · 02:18 · access logged</small></span><span class="wave">' . str_repeat('<i></i>', 36) . '</span><span class="app-tag ok">Outcome saved</span></div>';

    return mockup_shell('app.rumu.in/realtor/calls', ['Dashboard', 'Lead Board', 'Campaigns', 'Customers', 'Inventory', 'Tasks', 'Calls', 'Billing'], 'Calls', $content);
}

function mockup_inventory(): string
{
    $units = [
        ['A-1201', 'available'], ['A-1202', 'hold'], ['A-1203', 'booked'], ['A-1204', 'available'],
        ['A-1301', 'sold'], ['A-1302', 'available'], ['A-1303', 'available'], ['A-1304', 'hold'],
    ];
    $grid = '';
    foreach ($units as [$unit, $status]) {
        $grid .= '<span class="unit-chip unit-' . e($status) . '">' . e($unit) . '</span>';
    }

    $content = mockup_topbar('Realtor workspace', 'Inventory', 'RS', 'Skyline Residency')
        . '<div class="inv-head"><div><h4>Skyline Residency · Pune</h4><p>Project → Tower → Wing → Floor → Unit</p></div><span class="app-chip">Filters: 2–3 BHK</span></div>'
        . '<div class="inv-tree">'
        . '<div class="inv-col"><p class="app-eyebrow">Tower</p><span class="inv-node is-active">Tower A</span><span class="inv-node">Tower B</span><span class="inv-node">Tower C</span></div>'
        . '<div class="inv-col"><p class="app-eyebrow">Wing</p><span class="inv-node is-active">Wing 1</span><span class="inv-node">Wing 2</span></div>'
        . '<div class="inv-col"><p class="app-eyebrow">Floor</p><span class="inv-node">09</span><span class="inv-node">10</span><span class="inv-node">11</span><span class="inv-node is-active">12</span><span class="inv-node">13</span></div>'
        . '<div class="inv-col inv-units"><p class="app-eyebrow">Units</p><div class="unit-grid">' . $grid . '</div>'
        . '<div class="unit-legend"><span class="unit-available">Available</span><span class="unit-hold">On hold</span><span class="unit-booked">Booked</span><span class="unit-sold">Sold</span></div></div>'
        . '</div>';

    return mockup_shell('app.rumu.in/realtor/inventory', ['Dashboard', 'Lead Board', 'Campaigns', 'Customers', 'Inventory', 'Tasks', 'Calls', 'Billing'], 'Inventory', $content);
}

function mockup_reports(): string
{
    $content = mockup_topbar('Enterprise workspace', 'Reports & targets', 'PM', 'November')
        . '<div class="app-kpis app-kpis--three">'
        . '<div class="app-kpi"><span>Company target</span><strong>₹18 Cr</strong><small>Monthly</small></div>'
        . '<div class="app-kpi"><span>Booked value</span><strong>₹12.6 Cr</strong><small class="up">70% achieved</small></div>'
        . '<div class="app-kpi"><span>Units booked</span><strong>34</strong><small>Across 4 projects</small></div>'
        . '</div>'
        . '<div class="app-columns"><div class="app-panel"><div class="app-panel-head"><h4>Target vs actual</h4><span class="app-link">Export CSV</span></div>'
        . '<div class="chart">'
        . '<div class="chart-row"><span>Pune West</span><b class="bar bar-88"></b><em>₹4.4 Cr</em></div>'
        . '<div class="chart-row"><span>Bengaluru</span><b class="bar bar-70"></b><em>₹3.5 Cr</em></div>'
        . '<div class="chart-row"><span>Thane</span><b class="bar bar-52"></b><em>₹2.6 Cr</em></div>'
        . '<div class="chart-row"><span>Nagpur</span><b class="bar bar-28"></b><em>₹1.4 Cr</em></div>'
        . '</div></div>'
        . '<div class="app-panel"><div class="app-panel-head"><h4>Manager scorecard</h4><span class="app-link">This month</span></div>'
        . '<div class="app-table"><div class="app-table-row app-table-head"><span>Manager</span><span>Leads</span><span>Follow-ups</span><span>Won</span></div>'
        . '<div class="app-table-row"><span>Anita R.</span><span>412</span><span>96%</span><span>38</span></div>'
        . '<div class="app-table-row"><span>Rahul M.</span><span>356</span><span>91%</span><span>29</span></div>'
        . '<div class="app-table-row"><span>Zoya K.</span><span>301</span><span>88%</span><span>24</span></div>'
        . '</div></div></div>';

    return mockup_shell('app.rumu.in/enterprise/reports', ['Dashboard', 'Leads', 'Teams', 'Distribution', 'Targets', 'Reports', 'Inventory', 'Calls', 'Audit'], 'Reports', $content, 'Priya M.', 'Admin');
}

function mockup_org(): string
{
    $content = mockup_topbar('Enterprise workspace', 'Hierarchy & roles', 'PM')
        . '<div class="org">'
        . '<div class="org-level"><span class="org-node org-node--admin"><strong>Admin</strong><small>Company-wide visibility</small></span></div>'
        . '<div class="org-line"></div>'
        . '<div class="org-level org-level--managers"><span class="org-node"><strong>Manager · Pune West</strong><small>Team subtree</small></span><span class="org-node"><strong>Manager · Bengaluru</strong><small>Team subtree</small></span><span class="org-node"><strong>Manager · Thane</strong><small>Team subtree</small></span></div>'
        . '<div class="org-line"></div>'
        . '<div class="org-level org-level--employees"><span class="org-node org-node--sm">Employee</span><span class="org-node org-node--sm">Employee</span><span class="org-node org-node--sm">Employee</span><span class="org-node org-node--sm">Employee</span><span class="org-node org-node--sm">Employee</span><span class="org-node org-node--sm">Employee</span></div>'
        . '</div>'
        . '<div class="org-note"><span class="app-tag">Scoped by API</span> Each role only receives the data its membership and reporting line allow — the UI hides what the API refuses.</div>';

    return mockup_shell('app.rumu.in/enterprise/teams', ['Dashboard', 'Leads', 'Teams', 'Distribution', 'Targets', 'Reports', 'Inventory', 'Calls', 'Audit'], 'Teams', $content, 'Priya M.', 'Admin');
}

function mockup_audit(): string
{
    $rows = [
        ['Lead transferred', 'Anita R. → Rahul M.', 'Today, 4:12 PM'],
        ['Team member suspended', 'Karan T. · Pune West', 'Today, 2:40 PM'],
        ['Recording played', 'Riya S. call · by Admin', 'Today, 12:22 PM'],
        ['Bulk import', '212 leads · 6 skipped duplicates', 'Yesterday, 6:05 PM'],
    ];
    $list = '';
    foreach ($rows as [$action, $detail, $time]) {
        $list .= '<div class="audit-row"><span class="audit-dot"></span><span><strong>' . e($action) . '</strong><small>' . e($detail) . '</small></span><em>' . e($time) . '</em></div>';
    }
    $content = mockup_topbar('Enterprise workspace', 'Audit log', 'PM')
        . '<div class="app-panel"><div class="app-panel-head"><h4>Administrative activity</h4><span class="app-link">Filter</span></div><div class="audit-list">' . $list . '</div></div>'
        . '<div class="org-note"><span class="app-tag">Read-only trail</span> Member and administrative actions are recorded with actor and timestamp.</div>';

    return mockup_shell('app.rumu.in/enterprise/audit', ['Dashboard', 'Leads', 'Teams', 'Distribution', 'Targets', 'Reports', 'Inventory', 'Calls', 'Audit'], 'Audit', $content, 'Priya M.', 'Admin');
}

function mockup_app_wire(): string
{
    return '<figure class="app-wire"><div class="phone" aria-hidden="true">'
        . '<div class="phone-notch"></div><div class="phone-screen">'
        . '<div class="wire-top"><span></span><span class="wire-avatar"></span></div>'
        . '<div class="wire-block wire-hero"></div>'
        . '<div class="wire-cards"><span></span><span></span><span></span></div>'
        . '<div class="wire-list"><span></span><span></span><span></span><span></span></div>'
        . '<div class="wire-tabbar"><span></span><span></span><span></span><span></span></div>'
        . '</div></div><figcaption><span class="pill">Preview</span> Wireframe of the planned app — not a shipped app.</figcaption></figure>';
}

function ui_mockup(string $type, array $opts = []): string
{
    $caption = $opts['caption'] ?? 'Product interface preview with sample data.';
    $inner = match ($type) {
        'dashboard' => mockup_dashboard(false),
        'enterprise-dashboard' => mockup_dashboard(true),
        'lead-board' => mockup_lead_board(),
        'lead-detail' => mockup_lead_detail(false),
        'lead-calls' => mockup_lead_detail(true),
        'calls' => mockup_calls(),
        'inventory' => mockup_inventory(),
        'reports' => mockup_reports(),
        'org' => mockup_org(),
        'audit' => mockup_audit(),
        'app-wire' => mockup_app_wire(),
        default => mockup_dashboard(false),
    };

    if ($type === 'app-wire') {
        return $inner;
    }

    $label = $opts['aria_label'] ?? $caption;
    return '<figure class="product-preview product-preview--' . e($type) . '"><div role="img" aria-label="' . e($label) . '">' . $inner . '</div><figcaption>' . e($caption) . '</figcaption></figure>';
}
