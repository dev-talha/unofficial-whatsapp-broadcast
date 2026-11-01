# WhatsApp Bulk Broadcaster & Outreach Manager (with Anti-Ban Architecture)

A lightweight, high-performance, single-page web dashboard designed for targeted WhatsApp outreach, emergency broadcasts, and contact management using **Evolution API** and direct **WhatsApp Web/Desktop** integration.

This project was built to solve the challenges of **high-volume cold messaging**, **duplicate phone numbers**, **account suspension risks**, and **real-time delivery tracking** without requiring heavy database engines like MySQL or PostgreSQL.

---

## 📌 Features & Architecture

* **Zero-Database (File-Based Engine):** Uses clean, structured JSON storage (`donors.json`, `history.json`), making it ultra-fast, zero-maintenance, and easy to backup or migrate.
* **Docker Ready:** Built-in Docker & Docker Compose setup (`webdevops/php-nginx:8.2`) for instant one-command deployment on any Linux VPS or server.
* **PIN Authentication:** Secure session-based lock screen to prevent unauthorized access when exposed to a public IP/domain.
* **Smart Phone Normalization & Deduplication:** Cleans non-digit characters and normalizes numbers using last 10-digit matching (`017...`, `+88017...`, `88017...`) to strictly eliminate duplicate contacts during runtime and CSV imports.
* **CSV Bulk Importer:** Upload external contacts directly from CSV with auto-detection, column mapping, and duplicate filtering.
* **Hybrid Messaging Engine:**
  * **Automated API Mode:** Background automated sending through Evolution API with live HTTP status feedback.
  * **Manual Click-to-Chat Mode:** One-click launch into official **WhatsApp Web** or **WhatsApp Desktop** (`wa.me` URI) with pre-filled, customized messages.
* **Interactive Status Management:**
  * Real-time progress bar and delivery stats.
  * Clickable status badges to easily toggle between `Pending`, `Sent`, and `No WhatsApp` states.
  * Auto-save message templates in browser LocalStorage.

---

## 🛡️ Anti-Ban Protections Implemented (Code Level)

When conducting outreach via reverse-engineered protocols (e.g., Baileys / Evolution API), WhatsApp's automated anti-spam AI is the primary bottleneck. This system incorporates **5 layers of defensive architecture**:

1. **Pre-Validation / Availability Check (Zero Undelivered Penalty):**
   * Before firing a message, the system queries the WhatsApp server to confirm if the destination number has an active WhatsApp account.
   * Numbers without WhatsApp are automatically skipped, avoiding the immediate spam-score penalty associated with messaging non-existent accounts.
2. **Human-like Randomized Interval Delay:**
   * Instead of a static timer (e.g., sending every 3 seconds), each message is separated by a customizable **random delay (e.g., 6 to 15 seconds)** to mimic human typing and sending habits.
3. **Message Spintax & Variation (Digital Hash Randomization):**
   * Identical strings broadcasted across multiple chats are flagged rapidly by Meta's hash-matching algorithms.
   * The system randomizes opening greetings and injects dynamic recipient attributes (such as Name, Source Website, Organization) to ensure every outgoing payload produces a unique digital fingerprint.
4. **Typing Simulation Presence:**
   * Injects a presence simulation delay (`delay: 1200ms`) before dispatching payloads, making the remote connection report a "typing..." state to WhatsApp servers.
5. **Batch Segmentation & Real-Time Abort:**
   * Filter contacts by source or category to send in small batches.
   * Real-time **"Pause / Stop"** control allows instant termination if anomalies or rate-limits are detected.

---

## ⚠️ Anti-Ban Failure Analysis (Why Unofficial API Bots Still Get Banned)

Even with robust code-level protections, automated messaging on unverified WhatsApp numbers carries inherent risks. Understanding why bans occur is critical for any team building bulk outreach tools:

```
[Outreach Campaign]
       │
       ├──► High Volume + Unknown Contacts (Cold Audience)
       │        │
       │        └──► Zero Contact Book Reciprocity (Number not saved by recipient)
       │
       ├──► Zero Bidirectional Traffic (100 messages sent, 0 replies received in 1st hour)
       │
       ├──► User Block / Report Buttons (1-2 reports trigger immediate auto-ban)
       │
       └──► Reverse-Engineered Socket Traffic (Meta fingerprinting unofficial clients)
                │
                ▼
       [ACCOUNT SUSPENDED / BANNED]
```

### Key Technical Causes:
1. **Unilateral Cold Traffic (Zero Reciprocity):**
   * When an account sends 50+ messages to recipients who do not have the sender saved in their address book, WhatsApp's heuristic filter flags the activity as unsolicited outreach.
   * If there is no incoming dialogue (bidirectional response) within the first hour of sending, the trust score drops exponentially.
2. **User "Block & Report" Actions:**
   * WhatsApp prominently displays **"Block"** and **"Report Spam"** buttons when receiving messages from an unknown sender.
   * A single report from 1-2 users within a short window is often enough to trigger an automatic algorithmic suspension.
3. **Account Age & Warm-up History:**
   * New SIM cards, recently registered accounts, or dormant numbers with little organic history lack the "Trust Score" required for bulk communication.
4. **Unofficial WebSockets (Protocol Fingerprinting):**
   * Libraries like Baileys emulate WhatsApp Web. Meta continuously patches socket headers and behavior signatures to detect and sever automated sessions.

---

## 🚀 Golden Rules for Safe Outreach (Production Checklist)

To minimize suspension risks for any future marketing, alert, or emergency broadcast project, adhere to these guidelines:

* [ ] **Substantial Delay Windows:** Never use intervals below 15 seconds for cold contacts. Ideal range: **20 to 60 seconds** with random jitter.
* [ ] **Micro-Batches with Cool-down Periods:** Limit runs to **15–25 messages per session**, followed by a **2 to 3-hour cooling-off window**.
* [ ] **Warm-Up Your Numbers First:** Use numbers that are at least 3–6 months old and have a steady history of bidirectional daily messaging with real users.
* [ ] **Interactive Hook Messages:** Avoid sending long paragraphs in the first message. Use a short, polite query (e.g., *"Hello [Name], are you available for a quick question?"*) to encourage a reply. Once the user replies, subsequent messages are treated as consensual chat.
* [ ] **Use the Manual Web/Desktop Chat Fallback:** When delivering high-priority messages, leverage the dashboard's **`Chat`** button. Launching through official WhatsApp Web/Desktop completely eliminates API socket fingerprinting risks.
* [ ] **Official Meta Cloud API for Large Volumes:** For enterprise scale (>500 messages/day), always use the official **WhatsApp Business Platform / Cloud API** with approved message templates.

---

## 🛠️ Tech Stack

* **Backend:** PHP 8.2+
* **Frontend:** Bootstrap 5.3, Bootstrap Icons, Google Fonts (Hind Siliguri / Segoe UI), Vanilla JavaScript
* **Database:** Native Flat-File JSON (`donors.json`, `history.json`, `config.json`)
* **Integrations:** Evolution API (REST), WhatsApp Web/Desktop URI Scheme
* **Container:** Docker, Docker Compose, Nginx

---

## ⚙️ Quick Start (Local or VPS)

### 1. Clone the Repository
```bash
git clone https://github.com/<your-username>/whatsapp-broadcast-manager.git
cd whatsapp-broadcast-manager
```

### 2. Configure Settings
Review `config.json` (or configure via the Web UI Settings Modal):
```json
{
    "evolution_url": "https://your-evolution-api.com",
    "api_key": "YourSecretApiKey",
    "instance_name": "MainInstance",
    "sender_phone": "+8801XXXXXXXXX",
    "min_delay": 15,
    "max_delay": 35
}
```

### 3. Launch with Docker Compose
```bash
docker compose up -d
```

### 4. Access the Dashboard
Open your browser at:
```
http://<your-server-ip>:8999
```
* **Default Security PIN:** `7530` *(can be modified in `api.php`)*

---

## ⚖️ গুরুত্বপূর্ণ সতর্কবার্তা ও ডিসক্লেইমার (Important Disclaimer)

> **⚠️ নোট ও বাস্তব সত্যতা:**  
> এই রিপোজিটরিতে উল্লেখিত সকল অ্যান্টি-ব্যান কৌশল, র‍্যান্ডম ডিলে এবং সুরক্ষা ব্যবস্থা মেনে চললে হোয়াটসঅ্যাপ অ্যাকাউন্ট ব্যান বা রেস্ট্রিক্ট হওয়ার **ঝুঁকি বহুলাংশে কমে আসে—তবে এটি কোনো অবস্থাতেই ১০০% নিশ্চয়তা (No Guarantee) প্রদান করে না।**  
> 
> মেটা (Meta/WhatsApp) তাদের স্প্যাম ডিটেকশন অ্যালগরিদম, হিউরিস্টিক ফিল্টার এবং নিরাপত্তা নীতিমালা প্রতিনিয়ত ও নীরবে আপডেট করে থাকে। যেকোনো নন-অফিসিয়াল এপিআই (Unofficial WhatsApp API / WebSockets) দিয়ে অপরিচিত নম্বরে বাল্ক মেসেজ পাঠালে সবসময়ই অ্যাকাউন্টের ওপর ঝুঁকি বজায় থাকে।
> 
> **পরামর্শ:**  
> * আপনার মূল ব্যক্তিগত বা প্রাইমারি বিজনেস নম্বর দিয়ে কখনো কোল্ড আউটরিচ বা বাল্ক ব্রডকাস্ট চালাবেন না।  
> * শতভাগ ঝুঁকিমুক্ত ও অফিশিয়াল ব্যবহারের জন্য মেটার অনুমোদিত **WhatsApp Business Cloud API (Official)** ব্যবহার করার পরামর্শ দেওয়া হচ্ছে।

---

## 📄 License
This project is open-source and available under the [MIT License](LICENSE).
