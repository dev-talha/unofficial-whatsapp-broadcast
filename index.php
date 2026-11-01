<?php
session_start();
$isLoggedIn = !empty($_SESSION['authenticated']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>জরুরি B− ব্লাড ডোনার হোয়াটসঅ্যাপ ব্রডকাস্টার</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, button, input, select, textarea { font-family: 'Hind Siliguri', 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f8f9fa; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .badge-blood { background-color: #dc3545; color: white; font-size: 0.95rem; }
        .log-box { background: #1e1e2f; color: #a6accd; height: 380px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; padding: 12px; border-radius: 8px; }
        .log-success { color: #4ade80; }
        .log-warning { color: #facc15; }
        .log-danger { color: #f87171; }
        .log-info { color: #60a5fa; }
        .table-responsive { max-height: 480px; overflow-y: auto; }
        .pin-screen { max-width: 360px; margin: 100px auto; }
    </style>
</head>
<body class="py-4">

<!-- PIN Login Screen -->
<div id="pinScreen" class="container pin-screen <?php echo $isLoggedIn ? 'd-none' : ''; ?>">
    <div class="card p-4 text-center shadow-sm">
        <div class="mb-3">
            <i class="bi bi-shield-lock-fill text-danger display-4"></i>
        </div>
        <h4 class="fw-bold mb-1">অ্যাক্সেস পিন কোড দিন</h4>
        <p class="text-muted small mb-3">জরুরি ব্লাড ব্রডকাস্ট সিস্টেমে প্রবেশ করতে PIN দিন</p>
        <div class="mb-3">
            <input type="password" id="pinInput" class="form-control form-control-lg text-center fw-bold" maxlength="6" placeholder="••••" style="letter-spacing: 6px; font-size: 1.5rem;" autofocus onkeydown="if(event.key==='Enter') submitPin()">
            <div id="pinError" class="text-danger small mt-2 d-none">ভুল PIN দিয়েছেন! আবার চেষ্টা করুন।</div>
        </div>
        <button class="btn btn-danger btn-lg w-100 fw-bold" onclick="submitPin()">
            <i class="bi bi-box-arrow-in-right"></i> আনলক করুন
        </button>
    </div>
</div>

<!-- Main App Screen -->
<div id="mainApp" class="container-fluid px-4 <?php echo !$isLoggedIn ? 'd-none' : ''; ?>">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="mb-1 text-danger fw-bold"><i class="bi bi-droplet-fill"></i> B− ব্লাড ডোনার হোয়াটসঅ্যাপ ব্রডকাস্টার</h3>
            <p class="text-muted mb-0">রোগী: মোঃ শাহীন | জাতীয় হৃদরোগ ইনস্টিটিউট ও হাসপাতাল, ঢাকা | ওপেন হার্ট সার্জারি</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-outline-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> CSV ডোনার ইমপোর্ট
            </button>
            <button class="btn btn-outline-success btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#testModal" onclick="prepareTestModal()">
                <i class="bi bi-chat-dots-fill"></i> টেস্ট মেসেজ পাঠান
            </button>
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="bi bi-gear-fill"></i> Evolution সেটিংস
            </button>
            <span class="badge bg-danger p-2"><i class="bi bi-heart-pulse-fill"></i> B− Negative</span>
            <button class="btn btn-outline-danger btn-sm" onclick="logout()">
                <i class="bi bi-box-arrow-right"></i> লগআউট
            </button>
        </div>
    </div>

    <!-- Alert / Status Banner -->
    <div id="instanceStatusAlert" class="alert alert-info py-2 d-flex justify-content-between align-items-center mb-3">
        <span><i class="bi bi-info-circle-fill"></i> Evolution API Instance: <strong id="currentInstance">লোড হচ্ছে...</strong></span>
        <button class="btn btn-sm btn-link p-0 text-decoration-none" onclick="checkInstanceStatus()"><i class="bi bi-arrow-clockwise"></i> রিফ্রেশ স্ট্যাটাস</button>
    </div>

    <div class="row g-4">
        <!-- Left Column: Message Template & Settings -->
        <div class="col-lg-5">
            <div class="card p-3 mb-3">
                <h5 class="card-title fw-bold text-dark mb-3"><i class="bi bi-chat-quote-fill text-danger"></i> হোয়াটসঅ্যাপ মেসেজ ড্রাফট</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">মেসেজের শুরুতে রেন্ডম শুভেচ্ছা (Anti-Ban Spintax):</label>
                    <select id="greetingType" class="form-select form-select-sm">
                        <option value="salam">আসসালামু আলাইকুম। ভাই,</option>
                        <option value="direct">ভাই,</option>
                        <option value="random">র‍্যান্ডম ভ্যারিয়েশন (অ্যাকাউন্ট সেফটির জন্য সেরা)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold mb-0">মেসেজ বডি (হোয়াটসঅ্যাপ ফরম্যাট: *bold*, _italic_):</label>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="saveTemplateMessage()" style="font-size: 0.75rem;">
                            <i class="bi bi-floppy-fill"></i> টেমপ্লেট সেভ করুন
                        </button>
                    </div>
                    <textarea id="messageBody" class="form-control" rows="14" style="font-size: 0.9rem;" oninput="onMessageEdited()">{website_domain_only} থেকে আপনার নাম্বারটি পেয়েছি। একটি *অত্যন্ত জরুরি প্রয়োজনে* আপনার সঙ্গে যোগাযোগ করছি।

আমাদের একজন রোগী, *মোঃ শাহীন*, বর্তমানে *জাতীয় হৃদরোগ ইনস্টিটিউট ও হাসপাতালে (শেরেবাংলা নগর, ঢাকা)* ভর্তি আছেন। তাঁর ওপেন হার্ট সার্জারির জন্য জরুরি ভিত্তিতে *১০ ব্যাগ B− (বি নেগেটিভ) রক্ত* প্রয়োজন।

আলহামদুলিল্লাহ, ইতোমধ্যে ৩ ব্যাগ রক্তের ডোনার পাওয়া গেছে। এখনো *৭ ব্যাগ রক্তের ডোনার প্রয়োজন*। রক্তের ব্যবস্থা হলেই তাঁর অপারেশন করা সম্ভব হবে।

আমরা রোগীকে নিয়ে বগুড়া থেকে ঢাকায় এসেছি। বর্তমানে শুধু রক্তের অভাবে অপারেশনের জন্য অপেক্ষা করছি। ইতোমধ্যে বেশ কয়েকটি রক্তদাতা সংগঠন ও ব্লাড ডোনেশন প্ল্যাটফর্মে যোগাযোগ করেছি, কিন্তু *B− নেগেটিভ রক্তের ডোনার* খুঁজে পাওয়া খুবই কঠিন হয়ে পড়েছে।

ভাই, আপনি যদি *রক্ত দিতে সক্ষম হন*, তাহলে আমাদের জন্য অনেক বড় উপকার হবে। 

আপনার পক্ষে রক্ত দেওয়া সম্ভব হবে কি না, একটু জানালে খুব উপকৃত হব। আর যদি কোনো কারণে আপনার পক্ষে সম্ভব না হয়, তাহলে পরিচিত কোনো *B− রক্তদাতার* সঙ্গে যোগাযোগ করিয়ে দিলেও আমরা অনেক উপকৃত হব।

ধন্যবাদ ভাই। আল্লাহ আপনার ও আপনার পরিবারের মঙ্গল করুন। ❤️
*Phone/Whatsapp:* {sender_phone}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">আপনার ফোন / যোগাযোগ নম্বর ({sender_phone}):</label>
                    <input type="text" id="senderPhoneInput" class="form-control form-control-sm" placeholder="01XXXXXXXXX" value="+8801581184629">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">মিনিমাম ডিলে (সেকেন্ড):</label>
                        <input type="number" id="minDelay" class="form-control form-control-sm" value="6" min="3">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">ম্যাক্সিমাম ডিলে (সেকেন্ড):</label>
                        <input type="number" id="maxDelay" class="form-control form-control-sm" value="12" min="5">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button id="startBtn" class="btn btn-danger flex-grow-1" onclick="startBroadcasting()">
                        <i class="bi bi-send-fill"></i> ব্রডকাস্ট শুরু করুন
                    </button>
                    <button id="stopBtn" class="btn btn-secondary" onclick="stopBroadcasting()" disabled>
                        <i class="bi bi-pause-fill"></i> বন্ধ / পজ করুন
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Filter & Donors List -->
        <div class="col-lg-7">
            <!-- Filter Bar -->
            <div class="card p-3 mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold mb-1">ওয়েবসাইট ফিল্টার:</label>
                        <select id="siteFilter" class="form-select form-select-sm" onchange="renderDonorTable()">
                            <option value="all">সব ওয়েবসাইট (সকল ডোনার)</option>
                            <option value="donatebloodbd.com">donatebloodbd.com</option>
                            <option value="blood-bd.com">blood-bd.com</option>
                            <option value="roktodata.org">roktodata.org</option>
                            <option value="roktobondhu.com">roktobondhu.com</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">স্ট্যাটাস ফিল্টার:</label>
                        <select id="statusFilter" class="form-select form-select-sm" onchange="renderDonorTable()">
                            <option value="all">সব</option>
                            <option value="pending">বাকি আছে (Pending)</option>
                            <option value="sent">সফলভাবে পাঠানো হয়েছে</option>
                            <option value="nowhatsapp">হোয়াটসঅ্যাপ নেই</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <label class="form-label small fw-bold mb-1 d-block">সিলেক্টেড ডোনার:</label>
                        <span class="badge bg-primary" id="selectedCount">০</span> / <span id="totalCount">০</span>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between small fw-bold mb-1">
                    <span>ব্রডকাস্ট অগ্রগতি: <span id="progressText">০ / ০</span></span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span><i class="bi bi-check-circle-fill text-success"></i> সফল: <strong id="successCount">০</strong></span>
                    <span><i class="bi bi-x-circle-fill text-danger"></i> হোয়াটসঅ্যাপ নেই: <strong id="noWaCount">০</strong></span>
                    <span><i class="bi bi-exclamation-triangle-fill text-warning"></i> ফেইল্ড: <strong id="failCount">০</strong></span>
                </div>
            </div>

            <!-- Tabs: Table & Live Console -->
            <div class="card p-3">
                <ul class="nav nav-tabs mb-3" id="mainTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold" id="table-tab" data-bs-toggle="tab" data-bs-target="#tab-table">
                            <i class="bi bi-table"></i> ডোনার তালিকা
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold" id="log-tab" data-bs-toggle="tab" data-bs-target="#tab-log">
                            <i class="bi bi-terminal-fill"></i> লাইভ কনসোল লগ
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="mainTabContent">
                    <!-- Tab 1: Table -->
                    <div class="tab-pane fade show active" id="tab-table">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" checked>
                                    <label class="form-check-label small" for="selectAllCheckbox">সব সিলেক্ট করুন</label>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-info py-0" onclick="verifyAllPendingWhatsApp()" style="font-size: 0.78rem;">
                                    <i class="bi bi-patch-check"></i> সিলেক্টেড নাম্বারের হোয়াটসঅ্যাপ একসাথে চেক করুন
                                </button>
                            </div>
                            <small class="text-muted">চেকমার্ক করা ডোনারদেরই মেসেজ পাঠানো হবে</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle" style="font-size: 0.85rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="4%"></th>
                                        <th>নাম</th>
                                        <th>ফোন</th>
                                        <th>এলাকা</th>
                                        <th>ওয়েবসাইট</th>
                                        <th>হোয়াটসঅ্যাপ আছে?</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody id="donorTableBody">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Console Log -->
                    <div class="tab-pane fade" id="tab-log">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small text-muted">রিয়েল-টাইম প্রগ্রেস ও এরর লগ</span>
                            <button class="btn btn-sm btn-outline-secondary py-0" onclick="clearLogs()">ক্লিয়ার লগ</button>
                        </div>
                        <div id="logConsole" class="log-box">
                            <div>[সিস্টেম] প্রস্তুত। ব্রডকাস্ট শুরু করতে 'ব্রডকাস্ট শুরু করুন' চাপুন।</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-primary"><i class="bi bi-file-earmark-spreadsheet-fill"></i> CSV ফাইল থেকে নতুন ডোনার যুক্ত করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">CSV ফাইলের হেডার বা কলাম ফরম্যাট নিচের মতো হতে হবে:</p>
                <div class="bg-light p-2 rounded small font-monospace mb-3 border">
                    Name, Phone, Location, Site, Last Donation<br>
                    <span class="text-muted">যেমন: সুমন, 01712000000, মিরপুর, roktodata, 12 Jan 2026</span>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">CSV ফাইল নির্বাচন করুন:</label>
                    <input type="file" id="csvFileInput" class="form-control" accept=".csv">
                </div>

                <div id="importResultAlert" class="alert d-none py-2 small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">বন্ধ করুন</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold" id="uploadCsvBtn" onclick="submitCsvImport()">
                    <i class="bi bi-cloud-arrow-up-fill"></i> ইমপোর্ট করুন
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Single Test Message Modal -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-chat-dots-fill"></i> সিঙ্গেল টেস্ট মেসেজ পাঠান</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">বড় ব্রডকাস্ট চালানোর আগে যেকোনো নির্দিষ্ট নাম্বারে মেসেজ পাঠিয়ে টেস্ট করে নিতে পারেন।</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">টেস্ট প্রাপকের ফোন নম্বর:</label>
                    <input type="text" id="testPhoneInput" class="form-control" placeholder="01XXXXXXXXX" value="01581184629">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">কাল্পনিক ডোনারের নাম:</label>
                    <input type="text" id="testNameInput" class="form-control form-control-sm" value="টেস্ট ডোনার">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">মেসেজের প্রিভিউ:</label>
                    <textarea id="testMsgPreview" class="form-control" rows="8" style="font-size: 0.85rem;"></textarea>
                </div>
                <div id="testStatusAlert" class="alert d-none py-2 small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">বাতিল</button>
                <button type="button" class="btn btn-success btn-sm fw-bold" id="sendTestBtn" onclick="executeSingleTest()">
                    <i class="bi bi-send-fill"></i> টেস্ট মেসেজ পাঠান
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Evolution Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill"></i> Evolution API সেটিংস</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Evolution API URL:</label>
                    <input type="text" id="cfgUrl" class="form-control form-control-sm" placeholder="https://eapi.yourdomain.com">
                    <div class="form-text">লোকাল বা রিমোট যেকোনো Evolution API সার্ভারের সম্পূর্ণ URL (যেমন: <code>https://eapi.abutalha.com.bd</code>)</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">API Key:</label>
                    <input type="text" id="cfgApiKey" class="form-control form-control-sm" value="EvoApiKeySecure2026!#">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Instance Name:</label>
                    <div class="input-group input-group-sm">
                        <select id="cfgInstanceSelect" class="form-select">
                            <option value="DhakaOffice">DhakaOffice</option>
                        </select>
                        <button class="btn btn-outline-secondary" type="button" onclick="loadInstancesList()"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">বন্ধ করুন</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveSettings()">সেভ করুন</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let allDonors = [];
let sentHistory = {};
let config = {};
let isRunning = false;
let shouldStop = false;
let selectedDonorsList = [];

async function submitPin() {
    const pin = document.getElementById('pinInput').value.trim();
    const pinError = document.getElementById('pinError');
    if (!pin) return;

    try {
        const res = await fetch('api.php?action=verify_pin', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ pin: pin })
        });
        const data = await res.json();
        if (data.status === 'success') {
            document.getElementById('pinScreen').classList.add('d-none');
            document.getElementById('mainApp').classList.remove('d-none');
            loadData();
            checkInstanceStatus();
        } else {
            pinError.classList.remove('d-none');
            document.getElementById('pinInput').value = '';
            document.getElementById('pinInput').focus();
        }
    } catch (e) {
        alert('সার্ভার এরর: ' + e.message);
    }
}

async function submitCsvImport() {
    const fileInput = document.getElementById('csvFileInput');
    const alertBox = document.getElementById('importResultAlert');
    const btn = document.getElementById('uploadCsvBtn');

    if (!fileInput.files || fileInput.files.length === 0) {
        alert('অনুগ্রহ করে একটি CSV ফাইল সিলেক্ট করুন');
        return;
    }

    const formData = new FormData();
    formData.append('csv_file', fileInput.files[0]);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> প্রসেস হচ্ছে...';
    alertBox.className = 'alert alert-info py-2 small';
    alertBox.innerText = 'ফাইল আপলোড ও ডেটা প্রসেস করা হচ্ছে...';

    try {
        const res = await fetch('api.php?action=import_csv', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.status === 'success') {
            alertBox.className = 'alert alert-success py-2 small';
            alertBox.innerText = data.message;
            appendLog(`[CSV ইমপোর্ট] ${data.imported} জন নতুন ডোনার যোগ করা হয়েছে।`, 'success');
            fileInput.value = '';
            // Reload list
            loadData();
        } else {
            alertBox.className = 'alert alert-danger py-2 small';
            alertBox.innerText = data.message || 'ইমপোর্ট ব্যর্থ হয়েছে';
        }
    } catch (e) {
        alertBox.className = 'alert alert-danger py-2 small';
        alertBox.innerText = 'এরর: ' + e.message;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill"></i> ইমপোর্ট করুন';
    }
}

async function logout() {
    await fetch('api.php?action=logout');
    window.location.reload();
}

// Initialize
document.addEventListener("DOMContentLoaded", function() {
    loadSavedTemplate();
    const mainApp = document.getElementById('mainApp');
    if (!mainApp.classList.contains('d-none')) {
        loadData();
        checkInstanceStatus();
    }
});

function appendLog(msg, type = 'info') {
    const logBox = document.getElementById('logConsole');
    const time = new Date().toLocaleTimeString();
    const line = document.createElement('div');
    line.className = 'log-' + type;
    line.innerHTML = `[${time}] ${msg}`;
    logBox.appendChild(line);
    logBox.scrollTop = logBox.scrollHeight;
}

function clearLogs() {
    document.getElementById('logConsole').innerHTML = '';
}

async function loadData() {
    try {
        const res = await fetch('api.php?action=get_donors');
        const data = await res.json();
        if (data.status === 'success') {
            allDonors = data.donors;
            sentHistory = data.history || {};
            config = data.config || {};
            
            if (config.sender_phone) {
                document.getElementById('senderPhoneInput').value = config.sender_phone;
            }
            if (config.evolution_url) {
                document.getElementById('cfgUrl').value = config.evolution_url;
            }
            if (config.api_key) {
                document.getElementById('cfgApiKey').value = config.api_key;
            }
            if (config.min_delay) document.getElementById('minDelay').value = config.min_delay;
            if (config.max_delay) document.getElementById('maxDelay').value = config.max_delay;
            
            renderDonorTable();
            updateStats();
        }
    } catch (err) {
        appendLog('ডোনার ডেটা লোড করতে সমস্যা: ' + err.message, 'danger');
    }
}

async function checkInstanceStatus() {
    const alertBox = document.getElementById('instanceStatusAlert');
    const instName = document.getElementById('currentInstance');
    try {
        const res = await fetch('api.php?action=get_instances');
        const data = await res.json();
        if (data.status === 'success' && data.instances.length > 0) {
            const inst = data.instances.find(i => i.name === config.instance_name) || data.instances[0];
            instName.innerHTML = `<strong>${inst.name}</strong> (${inst.number || 'No number'}) - <span class="badge ${inst.connectionStatus === 'open' ? 'bg-success' : 'bg-warning text-dark'}">${inst.connectionStatus}</span>`;
            if (inst.connectionStatus !== 'open') {
                appendLog(`সতর্কতা: WhatsApp Instance '${inst.name}' কানেক্টেড নেই (${inst.connectionStatus})। অনুগ্রহ করে Evolution API-তে QR কোড স্ক্যান করে কানেক্ট রাখুন।`, 'warning');
            }
        }
    } catch (e) {
        instName.innerText = 'কানেক্ট করা সম্ভব হয়নি';
    }
}

function renderDonorTable() {
    const siteVal = document.getElementById('siteFilter').value;
    const statusVal = document.getElementById('statusFilter').value;
    const tbody = document.getElementById('donorTableBody');
    tbody.innerHTML = '';

    let displayed = 0;
    allDonors.forEach((d, idx) => {
        const phone = d.phone.replace(/[^0-9]/g, '');
        const hist = sentHistory[phone] || sentHistory['88' + phone];
        const isSent = hist && hist.status === 'sent';
        const isNoWa = hist && hist.status === 'nowhatsapp';

        // Filters
        if (siteVal !== 'all' && d.site !== siteVal) return;
        if (statusVal === 'pending' && (isSent || isNoWa)) return;
        if (statusVal === 'sent' && !isSent) return;
        if (statusVal === 'nowhatsapp' && !isNoWa) return;

        displayed++;
        const row = document.createElement('tr');
        row.id = `donor-row-${idx}`;

        let statusBadge = '<span class="badge bg-secondary cursor-pointer" onclick="toggleManualStatus(' + idx + ')" style="cursor:pointer;" title="ক্লিক করে স্ট্যাটাস পরিবর্তন করুন">Pending</span>';
        if (isSent) {
            const timeStr = hist.sent_at ? hist.sent_at.split(' ')[1] : '';
            statusBadge = `<span class="badge bg-success" onclick="toggleManualStatus(${idx})" style="cursor:pointer;" title="ক্লিক করে পেন্ডিং করুন"><i class="bi bi-check-all"></i> Sent ${timeStr ? '(' + timeStr + ')' : ''}</span>`;
        } else if (isNoWa) {
            statusBadge = `<span class="badge bg-danger" onclick="toggleManualStatus(${idx})" style="cursor:pointer;" title="ক্লিক করে পেন্ডিং করুন"><i class="bi bi-x-circle"></i> No WhatsApp</span>`;
        }

        // WhatsApp Available Column Badge (Clickable to manually toggle)
        let waStatusBadge = '';
        if (isSent) {
            waStatusBadge = `<span class="badge bg-success-subtle text-success border border-success" onclick="toggleWhatsAppAvailable(${idx})" style="cursor:pointer;" title="ক্লিক করে Not Found বা আনচেক করুন"><i class="bi bi-check-circle-fill"></i> Available</span>`;
        } else if (isNoWa) {
            waStatusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger" onclick="toggleWhatsAppAvailable(${idx})" style="cursor:pointer;" title="ক্লিক করে Available বা আনচেক করুন"><i class="bi bi-x-circle-fill"></i> Not Found</span>`;
        } else {
            waStatusBadge = `
                <div class="d-flex gap-1 align-items-center">
                    <button class="btn btn-sm btn-outline-info py-0" style="font-size:0.75rem;" onclick="verifySingleWhatsApp(${idx})" title="API দিয়ে চেক করুন"><i class="bi bi-search"></i> চেক</button>
                    <button class="btn btn-sm btn-outline-danger py-0" style="font-size:0.72rem;" onclick="markDonorStatus(${idx}, 'nowhatsapp')" title="ম্যানুয়ালি 'Not Found' মার্ক করুন"><i class="bi bi-x-circle"></i> নেই</button>
                </div>
            `;
        }

        row.innerHTML = `
            <td>
                <input type="checkbox" class="donor-checkbox form-check-input" value="${idx}" ${isSent || isNoWa ? '' : 'checked'} onchange="updateSelectedCount()">
            </td>
            <td><strong>${d.name}</strong></td>
            <td><code>${d.phone}</code></td>
            <td><small class="text-muted">${d.location || '-'}</small></td>
            <td><span class="badge bg-light text-dark border">${d.site}</span></td>
            <td id="wa-check-col-${idx}">${waStatusBadge}</td>
            <td id="status-col-${idx}">${statusBadge}</td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary py-0" onclick="sendSingleMessage(${idx})" title="Evolution API দিয়ে স্বয়ংক্রিয়ভাবে পাঠান">
                        <i class="bi bi-send"></i> API
                    </button>
                    <button class="btn btn-sm btn-outline-success py-0" onclick="openManualWhatsApp(${idx})" title="WhatsApp Web / App এ সরাসরি চ্যাট খুলুন">
                        <i class="bi bi-whatsapp"></i> Chat
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    document.getElementById('totalCount').innerText = allDonors.length;
    updateSelectedCount();
}

function toggleSelectAll(master) {
    const cbs = document.querySelectorAll('.donor-checkbox');
    cbs.forEach(cb => cb.checked = master.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const cbs = document.querySelectorAll('.donor-checkbox:checked');
    document.getElementById('selectedCount').innerText = cbs.length;
}

function updateStats() {
    let sent = 0, noWa = 0, fail = 0;
    Object.values(sentHistory).forEach(h => {
        if (h.status === 'sent') sent++;
        else if (h.status === 'nowhatsapp') noWa++;
        else if (h.status === 'failed') fail++;
    });
    document.getElementById('successCount').innerText = sent;
    document.getElementById('noWaCount').innerText = noWa;
    document.getElementById('failCount').innerText = fail;
}

function openManualWhatsApp(idx) {
    const donor = allDonors[idx];
    let phone = donor.phone.replace(/[^0-9]/g, '');
    if (phone.length === 11 && phone.startsWith('01')) {
        phone = '88' + phone;
    }
    const message = generateMessage(donor);
    const encodedMsg = encodeURIComponent(message);
    const waUrl = `https://wa.me/${phone}?text=${encodedMsg}`;
    
    // Open in new tab (triggers WhatsApp Web or Desktop App)
    window.open(waUrl, '_blank');
    appendLog(`[ম্যানুয়াল চ্যাট] ${donor.name} (${donor.phone}) এর জন্য WhatsApp ওপেন করা হয়েছে।`, 'info');

    // Automatically mark as sent
    markDonorStatus(idx, 'sent');
}

async function toggleManualStatus(idx) {
    const donor = allDonors[idx];
    const phone = donor.phone.replace(/[^0-9]/g, '');
    const hist = sentHistory[phone] || sentHistory['88' + phone];
    const isSent = hist && hist.status === 'sent';

    const nextStatus = isSent ? 'pending' : 'sent';
    await markDonorStatus(idx, nextStatus);
}

async function toggleWhatsAppAvailable(idx) {
    const donor = allDonors[idx];
    const phone = donor.phone.replace(/[^0-9]/g, '');
    const hist = sentHistory[phone] || sentHistory['88' + phone];
    const currentStatus = hist ? hist.status : 'pending';

    // Toggle flow: nowhatsapp -> pending -> nowhatsapp
    if (currentStatus === 'nowhatsapp') {
        await markDonorStatus(idx, 'pending');
    } else {
        await markDonorStatus(idx, 'nowhatsapp');
    }
}

async function markDonorStatus(idx, status) {
    const donor = allDonors[idx];
    let cleanPhone = donor.phone.replace(/[^0-9]/g, '');
    const fullPhone = cleanPhone.length === 11 && cleanPhone.startsWith('01') ? '88' + cleanPhone : cleanPhone;

    try {
        const res = await fetch('api.php?action=mark_status', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                phone: donor.phone,
                status: status,
                name: donor.name,
                site: donor.site
            })
        });
        const data = await res.json();
        if (data.status === 'success') {
            if (status === 'pending') {
                delete sentHistory[cleanPhone];
                delete sentHistory[fullPhone];
                appendLog(`[স্ট্যাটাস] ${donor.name} (${donor.phone}) কে 'Pending' হিসেবে মার্ক করা হয়েছে।`, 'warning');
            } else {
                sentHistory[fullPhone] = {
                    status: status,
                    sent_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
                    donor_name: donor.name,
                    site: donor.site
                };
                appendLog(`[স্ট্যাটাস] ${donor.name} (${donor.phone}) কে 'Sent' হিসেবে মার্ক করা হয়েছে।`, 'success');
            }
            renderDonorTable();
            updateStats();
        }
    } catch (e) {
        console.error('স্ট্যাটাস আপডেট এরর:', e);
    }
}

async function verifySingleWhatsApp(idx) {
    const donor = allDonors[idx];
    const col = document.getElementById(`wa-check-col-${idx}`);
    col.innerHTML = '<span class="spinner-border spinner-border-sm text-info"></span>';

    try {
        const checkRes = await fetch('api.php?action=check_whatsapp', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                phone: donor.phone,
                name: donor.name,
                site: donor.site 
            })
        });
        const checkData = await checkRes.json();
        
        let cleanPhone = donor.phone.replace(/[^0-9]/g, '');
        const fullPhone = cleanPhone.length === 11 && cleanPhone.startsWith('01') ? '88' + cleanPhone : cleanPhone;

        if (checkData.status !== 'success') {
            col.innerHTML = '<span class="badge bg-warning text-dark border"><i class="bi bi-exclamation-triangle"></i> Not Connected</span>';
            appendLog(`[যাচাই ব্যর্থ] ${donor.name}: ${checkData.message || 'Instance Disconnected'}`, 'warning');
            return;
        }

        if (checkData.exists) {
            col.innerHTML = '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle-fill"></i> Available</span>';
            appendLog(`[হোয়াটসঅ্যাপ যাচাই] ${donor.name} (${donor.phone}) এ সক্রিয় হোয়াটসঅ্যাপ আছে।`, 'success');
        } else {
            col.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-x-circle-fill"></i> Not Found</span>';
            sentHistory[fullPhone] = {
                status: 'nowhatsapp',
                sent_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
                donor_name: donor.name,
                site: donor.site
            };
            const statusCol = document.getElementById(`status-col-${idx}`);
            if (statusCol) {
                statusCol.innerHTML = `<span class="badge bg-danger" onclick="toggleManualStatus(${idx})" style="cursor:pointer;" title="ক্লিক করে পেন্ডিং করুন"><i class="bi bi-x-circle"></i> No WhatsApp</span>`;
            }
            appendLog(`[হোয়াটসঅ্যাপ যাচাই] ${donor.name} (${donor.phone}) এ হোয়াটসঅ্যাপ পাওয়া যায়নি!`, 'danger');
            updateStats();
        }
    } catch (e) {
        col.innerHTML = '<span class="text-danger small">এরর</span>';
    }
}

async function verifyAllPendingWhatsApp() {
    const cbs = Array.from(document.querySelectorAll('.donor-checkbox:checked'));
    if (cbs.length === 0) {
        alert('অনুগ্রহ করে অন্তত একজন ডোনার সিলেক্ট করুন');
        return;
    }
    
    appendLog(`=== ${cbs.length} জন ডোনারের হোয়াটসঅ্যাপ যাচাই শুরু হয়েছে ===`, 'info');
    for (let cb of cbs) {
        const idx = parseInt(cb.value);
        await verifySingleWhatsApp(idx);
        await new Promise(r => setTimeout(r, 600)); // Short pause
    }
    appendLog('=== হোয়াটসঅ্যাপ যাচাই সম্পন্ন হয়েছে ===', 'success');
}

function saveTemplateMessage() {
    const text = document.getElementById('messageBody').value;
    const greeting = document.getElementById('greetingType').value;
    localStorage.setItem('saved_blood_message_template', text);
    localStorage.setItem('saved_blood_greeting_type', greeting);
    alert('মেসেজ টেমপ্লেট সফলভাবে ব্রাউজারে সংরক্ষিত হয়েছে! পেজ রিলোড হলেও এটি অক্ষুণ্ণ থাকবে।');
    appendLog('মেসেজ টেমপ্লেট লোকাল স্টোরেজে সেভ করা হয়েছে।', 'success');
}

function loadSavedTemplate() {
    const saved = localStorage.getItem('saved_blood_message_template');
    const savedGreeting = localStorage.getItem('saved_blood_greeting_type');
    if (saved) {
        document.getElementById('messageBody').value = saved;
    }
    if (savedGreeting) {
        document.getElementById('greetingType').value = savedGreeting;
    }
}

function onMessageEdited() {
    const text = document.getElementById('messageBody').value;
    localStorage.setItem('saved_blood_message_template', text);
}

function generateMessage(donor) {
    const greetingType = document.getElementById('greetingType').value;
    let greeting = "আসসালামু আলাইকুম।\n\nভাই, ";
    if (greetingType === 'direct') {
        greeting = "ভাই, ";
    } else if (greetingType === 'random') {
        const variants = [
            "আসসালামু আলাইকুম ভাই,",
            "আসসালামু আলাইকুম। ভাই,",
            "আসসালামু আলাইকুম ভাই, আশা করি ভালো আছেন।",
            "আসসালামু আলাইকুম ভাই, জরুরি প্রয়োজনে নক দিচ্ছি।"
        ];
        greeting = variants[Math.floor(Math.random() * variants.length)] + "\n\n";
    }

    let template = document.getElementById('messageBody').value;
    const senderPhone = document.getElementById('senderPhoneInput').value.trim();

    template = template.replace('{website_domain_only}', donor.site);
    template = template.replace('{name}', donor.name);
    template = template.replace('{sender_phone}', senderPhone);

    return greeting + template;
}

async function sendSingleMessage(idx) {
    const donor = allDonors[idx];
    const statusCol = document.getElementById(`status-col-${idx}`);
    statusCol.innerHTML = '<span class="badge bg-info text-dark"><span class="spinner-border spinner-border-sm"></span> চেকিং...</span>';

    appendLog(`[চেক] ${donor.name} (${donor.phone}) হোয়াটসঅ্যাপে আছে কিনা ভ্যালিডেট করা হচ্ছে...`, 'info');

    // 1. Pre-validation check
    try {
        const checkRes = await fetch('api.php?action=check_whatsapp', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ phone: donor.phone })
        });
        const checkData = await checkRes.json();
        
        if (!checkData.exists) {
            appendLog(`[বাদ] ${donor.name} (${donor.phone}) এর কোনো হোয়াটসঅ্যাপ অ্যাকাউন্ট নেই। স্কিপ করা হলো।`, 'danger');
            statusCol.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> No WhatsApp</span>';
            sentHistory[donor.phone] = { status: 'nowhatsapp', sent_at: new Date().toISOString() };
            updateStats();
            return false;
        }

        // 2. Send Message
        statusCol.innerHTML = '<span class="badge bg-primary"><span class="spinner-border spinner-border-sm"></span> সেন্ডিং...</span>';
        const msgText = generateMessage(donor);
        
        const sendRes = await fetch('api.php?action=send_message', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                phone: donor.phone,
                message: msgText,
                name: donor.name,
                site: donor.site
            })
        });
        const sendData = await sendRes.json();

        if (sendData.status === 'success') {
            appendLog(`[সফল] ${donor.name} (${donor.phone})-কে সফলভাবে মেসেজ পাঠানো হয়েছে।`, 'success');
            statusCol.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-all"></i> Sent</span>';
            sentHistory[donor.phone] = { status: 'sent', sent_at: new Date().toISOString() };
            updateStats();
            return true;
        } else {
            appendLog(`[ব্যর্থ] ${donor.name} (${donor.phone})-কে মেসেজ পাঠানো যায়নি: ${sendData.response?.message || 'Error'}`, 'danger');
            statusCol.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Failed</span>';
            updateStats();
            return false;
        }
    } catch (e) {
        appendLog(`[এরর] ${donor.name}: ` + e.message, 'danger');
        statusCol.innerHTML = '<span class="badge bg-danger">Error</span>';
        return false;
    }
}

async function startBroadcasting() {
    const cbs = Array.from(document.querySelectorAll('.donor-checkbox:checked'));
    if (cbs.length === 0) {
        alert('অনুগ্রহ করে অন্তত একজন ডোনার সিলেক্ট করুন।');
        return;
    }

    const minDelay = parseInt(document.getElementById('minDelay').value) || 6;
    const maxDelay = parseInt(document.getElementById('maxDelay').value) || 12;

    isRunning = true;
    shouldStop = false;
    document.getElementById('startBtn').disabled = true;
    document.getElementById('stopBtn').disabled = false;

    // Switch to console tab
    const triggerTab = new bootstrap.Tab(document.getElementById('log-tab'));
    triggerTab.show();

    appendLog(`=== ব্রডকাস্ট শুরু হয়েছে (${cbs.length} জন ডোনার) ===`, 'info');

    let processed = 0;
    const total = cbs.length;

    for (let cb of cbs) {
        if (shouldStop) {
            appendLog('=== ব্রডকাস্ট ব্যবহারকারী দ্বারা বন্ধ/পজ করা হয়েছে ===', 'warning');
            break;
        }

        const idx = parseInt(cb.value);
        await sendSingleMessage(idx);

        processed++;
        const percent = Math.round((processed / total) * 100);
        document.getElementById('progressBar').style.width = percent + '%';
        document.getElementById('progressPercent').innerText = percent + '%';
        document.getElementById('progressText').innerText = `${processed} / ${total}`;

        if (processed < total && !shouldStop) {
            const delay = Math.floor(Math.random() * (maxDelay - minDelay + 1)) + minDelay;
            appendLog(`অ্যাকাউন্ট সুরক্ষার জন্য পরবর্তী মেসেজের আগে ${delay} সেকেন্ড বিরতি নেওয়া হচ্ছে...`, 'info');
            await new Promise(r => setTimeout(r, delay * 1000));
        }
    }

    isRunning = false;
    document.getElementById('startBtn').disabled = false;
    document.getElementById('stopBtn').disabled = true;
    appendLog('=== ব্রডকাস্ট সেশন সম্পন্ন হয়েছে ===', 'success');
}

function stopBroadcasting() {
    shouldStop = true;
    document.getElementById('stopBtn').disabled = true;
    appendLog('বর্তমান প্রসেস শেষ করে ব্রডকাস্ট বন্ধ করা হচ্ছে...', 'warning');
}

function prepareTestModal() {
    const dummyDonor = {
        name: document.getElementById('testNameInput').value || 'টেস্ট ডোনার',
        site: 'donatebloodbd.com',
        phone: document.getElementById('testPhoneInput').value
    };
    document.getElementById('testMsgPreview').value = generateMessage(dummyDonor);
    const alertBox = document.getElementById('testStatusAlert');
    alertBox.className = 'alert d-none py-2 small';
    alertBox.innerText = '';
}

async function executeSingleTest() {
    const phone = document.getElementById('testPhoneInput').value.trim();
    const name = document.getElementById('testNameInput').value.trim() || 'টেস্ট ডোনার';
    const message = document.getElementById('testMsgPreview').value;
    const btn = document.getElementById('sendTestBtn');
    const alertBox = document.getElementById('testStatusAlert');

    if (!phone) {
        alert('অনুগ্রহ করে টেস্ট ফোন নম্বর দিন');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> যাচাই ও পাঠানো হচ্ছে...';
    alertBox.className = 'alert alert-info py-2 small';
    alertBox.innerText = 'নম্বরটি হোয়াটসঅ্যাপে আছে কিনা চেক করা হচ্ছে...';

    try {
        // 1. Check whatsapp
        const checkRes = await fetch('api.php?action=check_whatsapp', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ phone: phone })
        });
        const checkData = await checkRes.json();
        
        if (!checkData.exists) {
            alertBox.className = 'alert alert-danger py-2 small';
            alertBox.innerText = 'এই নম্বরে কোনো সক্রিয় হোয়াটসঅ্যাপ অ্যাকাউন্ট পাওয়া যায়নি!';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> টেস্ট মেসেজ পাঠান';
            return;
        }

        alertBox.innerText = 'হোয়াটসঅ্যাপ পাওয়া গেছে! মেসেজ সেন্ড করা হচ্ছে...';

        // 2. Send message
        const sendRes = await fetch('api.php?action=send_message', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                phone: phone,
                message: message,
                name: name,
                site: 'test'
            })
        });
        const sendData = await sendRes.json();

        if (sendData.status === 'success') {
            alertBox.className = 'alert alert-success py-2 small';
            alertBox.innerText = 'টেস্ট মেসেজ সফলভাবে হোয়াটসঅ্যাপে চলে গেছে!';
            appendLog(`[টেস্ট সফল] ${phone} নম্বরে টেস্ট মেসেজ পাঠানো হয়েছে।`, 'success');
        } else {
            alertBox.className = 'alert alert-danger py-2 small';
            alertBox.innerText = 'মেসেজ পাঠানো যায়নি: ' + (sendData.response?.message || 'Evolution API Error');
            appendLog(`[টেস্ট ব্যর্থ] ${phone}: ` + (sendData.response?.message || 'Error'), 'danger');
        }
    } catch (e) {
        alertBox.className = 'alert alert-danger py-2 small';
        alertBox.innerText = 'এরর: ' + e.message;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> টেস্ট মেসেজ পাঠান';
    }
}

async function loadInstancesList() {
    const select = document.getElementById('cfgInstanceSelect');
    select.innerHTML = '<option>লোড হচ্ছে...</option>';
    try {
        const res = await fetch('api.php?action=get_instances');
        const data = await res.json();
        if (data.status === 'success') {
            select.innerHTML = '';
            data.instances.forEach(inst => {
                const opt = document.createElement('option');
                opt.value = inst.name;
                opt.innerText = `${inst.name} (${inst.connectionStatus})`;
                if (inst.name === config.instance_name) opt.selected = true;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        select.innerHTML = '<option>ব্যর্থ</option>';
    }
}

async function saveSettings() {
    const evoUrl = document.getElementById('cfgUrl').value.trim();
    const inst = document.getElementById('cfgInstanceSelect').value;
    const key = document.getElementById('cfgApiKey').value.trim();
    const minDelay = document.getElementById('minDelay').value;
    const maxDelay = document.getElementById('maxDelay').value;
    const senderPhone = document.getElementById('senderPhoneInput').value;

    const res = await fetch('api.php?action=save_config', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            evolution_url: evoUrl,
            instance_name: inst,
            api_key: key,
            sender_phone: senderPhone,
            min_delay: minDelay,
            max_delay: maxDelay
        })
    });
    const d = await res.json();
    if (d.status === 'success') {
        alert('সেটিংস সফলভাবে সংরক্ষিত হয়েছে।');
        bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
        loadData();
        checkInstanceStatus();
    }
}
</script>

</body>
</html>
