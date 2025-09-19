<x-layout-dashboard title="Message Monitor">

    <style>
        .message-item {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .message-item:hover {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .message-item.unread {
            background-color: #e8f4fd;
            border-left: 4px solid #007bff;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .message-time {
            font-size: 0.8em;
            color: #6c757d;
        }

        .message-device {
            font-size: 0.75em;
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
        }

        .message-sender {
            font-weight: 600;
            color: #007bff;
            font-size: 0.9em;
        }

        .message-content {
            margin-top: 8px;
            word-wrap: break-word;
            line-height: 1.4;
        }

        .message-type {
            font-size: 0.7em;
            background: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 8px;
        }

        .message-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 8px;
        }

        .message-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .email-list {
            padding: 15px;
        }

        .search-messages {
            border: 1px solid #ddd;
        }

        /* Custom scrollbar */
        #messagesContainer::-webkit-scrollbar {
            width: 6px;
        }

        #messagesContainer::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #messagesContainer::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        #messagesContainer::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif

    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Message Monitor</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">WhatsApp Monitor</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
            <div class="btn-group">
                <span class="badge bg-success me-2" id="status">🟢 Connected</span>
                <span class="badge bg-info" id="messageCount">0 messages</span>
            </div>
        </div>
    </div>
    <!--end breadcrumb-->

    <div class="email-wrapper">
        <!-- Sidebar for Devices & Campaigns -->
        <div class="email-sidebar">
            <div class="email-sidebar-header d-grid">
                <div class="btn-group mb-2">
                    <button class="btn btn-outline-success btn-sm" id="toggleSound">
                        <i class="bx bx-volume-full"></i> Sound
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="clearMessages()">
                        <i class="bx bx-trash"></i> Clear
                    </button>
                </div>
            </div>
            <div class="email-sidebar-content">
                <div class="email-navigation">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <h6 class="mb-2">📱 Active Devices</h6>
                            @if ($devices->count() > 0)
                                @foreach ($devices as $device)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input device-filter" type="checkbox"
                                            value="{{ $device->id }}" id="device{{ $device->id }}" checked>
                                        <label class="form-check-label" for="device{{ $device->id }}">
                                            <strong>***{{ substr($device->body, -4) }}</strong>
                                            <br><small class="text-muted">({{ $device->status }})</small>
                                        </label>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted small">No connected devices found</p>
                            @endif
                        </div>

                        @if ($activeCampaigns->count() > 0)
                            <div class="list-group-item">
                                <h6 class="mb-2">🚀 Active Campaigns</h6>
                                @foreach ($activeCampaigns as $campaign)
                                    <div class="mb-2">
                                        <small
                                            class="text-muted">***{{ substr($campaign->device->body, -4) }}</small><br>
                                        <strong
                                            class="small">{{ \Illuminate\Support\Str::limit($campaign->name, 20) }}</strong>
                                        <span class="badge bg-warning text-dark">Processing</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="email-content">
            <div class="email-header d-xl-flex align-items-center">
                <div class="d-flex align-items-center">
                    <div class="email-toggle-btn"><i class='bx bx-menu'></i></div>
                    <h5 class="mb-0 ms-2">💬 WhatsApp Messages - Live Monitor</h5>
                </div>
                <div class="flex-grow-1 mx-xl-2 my-2 my-xl-0">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control search-messages" placeholder="Search messages"
                            id="searchMessages">
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary btn-sm" onclick="refreshMessages()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-success btn-sm" onclick="exportMessages()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="email-list" style="height: 600px; overflow-y: auto;" id="messagesContainer">
                <div class="text-center text-muted p-4">
                    <i class="bx bx-message-dots bx-lg mb-3"></i>
                    <p>Waiting for incoming messages...</p>
                    <small>Messages will appear here in real-time</small>
                </div>
            </div>
        </div>
    </div>

    <!--start email overlay-->
    <div class="overlay email-toggle-btn-mobile">Click to close tab</div>
    <!--end email overlay-->


    <script>
        let lastCheck = new Date().toISOString();
        let messageCount = 0;
        let soundEnabled = true;
        let allMessages = [];

        function fetchMessages() {
            const selectedDevices = Array.from(document.querySelectorAll('.device-filter:checked'))
                .map(cb => cb.value);

            if (selectedDevices.length === 0) return;

            fetch('/monitor/messages?' + new URLSearchParams({
                    since: lastCheck,
                    device_id: selectedDevices.join(',')
                }))
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(addMessage);
                        lastCheck = new Date().toISOString();
                    }

                    // Update status
                    document.getElementById('status').innerHTML = '🟢 Connected';
                    document.getElementById('status').className = 'badge bg-success';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('status').innerHTML = '🔴 Error';
                    document.getElementById('status').className = 'badge bg-danger';
                });
        }

        function addMessage(message) {
            const container = document.getElementById('messagesContainer');

            // Remove "waiting" message if exists
            const waiting = container.querySelector('.text-center');
            if (waiting) waiting.remove();

            // Add to allMessages array for search
            allMessages.unshift(message);

            const messageDiv = document.createElement('div');
            messageDiv.className = 'message-item unread';
            messageDiv.setAttribute('data-message-id', message.id);
            messageDiv.innerHTML = `
            <div class="message-header">
                <div class="message-meta">
                    <span class="message-device">***${message.device}</span>
                    <span class="message-sender">${message.sender}</span>
                    <span class="message-type">${message.type}</span>
                </div>
                <span class="message-time">${message.time}</span>
            </div>
            <div class="message-content">
                ${message.content}
            </div>
        `;

            // Add click event to mark as read
            messageDiv.addEventListener('click', function() {
                this.classList.remove('unread');
                markMessageAsRead(message.id);
            });

            // Add to top
            container.insertBefore(messageDiv, container.firstChild);

            // Auto scroll to top
            container.scrollTop = 0;

            // Remove unread after 5 seconds
            setTimeout(() => {
                messageDiv.classList.remove('unread');
            }, 5000);

            // Update counter
            messageCount++;
            document.getElementById('messageCount').textContent = `${messageCount} messages`;

            // Keep only last 100 messages
            const messages = container.querySelectorAll('.message-item');
            if (messages.length > 100) {
                messages[messages.length - 1].remove();
                allMessages = allMessages.slice(0, 100);
            }

            // Sound notification
            if (soundEnabled) {
                playNotificationSound();
            }
        }

        function clearMessages() {
            const container = document.getElementById('messagesContainer');
            container.innerHTML = `
            <div class="text-center text-muted p-4">
                <i class="bx bx-message-dots bx-lg mb-3"></i>
                <p>Waiting for incoming messages...</p>
                <small>Messages will appear here in real-time</small>
            </div>
        `;
            messageCount = 0;
            allMessages = [];
            document.getElementById('messageCount').textContent = '0 messages';
        }

        function refreshMessages() {
            lastCheck = new Date(Date.now() - 300000).toISOString(); // Last 5 minutes
            fetchMessages();
        }

        function exportMessages() {
            if (allMessages.length === 0) {
                alert('No messages to export');
                return;
            }

            const csvContent = "data:text/csv;charset=utf-8," +
                "Device,Sender,Content,Type,Time\n" +
                allMessages.map(msg =>
                    `"***${msg.device}","${msg.sender}","${msg.content.replace(/"/g, '""')}","${msg.type}","${msg.time}"`
                ).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `whatsapp_messages_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function markMessageAsRead(messageId) {
            fetch('/monitor/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    ids: [messageId]
                })
            });
        }

        function playNotificationSound() {
            try {
                // Create beep sound using Web Audio API
                const audioContext = new(window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = 800;
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (e) {
                // Silent fail if audio not supported
            }
        }

        // Search functionality
        document.getElementById('searchMessages').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const messages = document.querySelectorAll('.message-item');

            messages.forEach(message => {
                const content = message.textContent.toLowerCase();
                if (content.includes(searchTerm)) {
                    message.style.display = 'block';
                } else {
                    message.style.display = 'none';
                }
            });
        });

        // Toggle sound
        document.getElementById('toggleSound').addEventListener('click', function() {
            soundEnabled = !soundEnabled;
            const icon = this.querySelector('i');
            if (soundEnabled) {
                icon.className = 'bx bx-volume-full';
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-outline-success');
            } else {
                icon.className = 'bx bx-volume-mute';
                this.classList.remove('btn-outline-success');
                this.classList.add('btn-outline-secondary');
            }
        });

        // Start monitoring
        setInterval(fetchMessages, 3000); // Check every 3 seconds

        // Device filter change
        document.querySelectorAll('.device-filter').forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    lastCheck = new Date(Date.now() - 60000).toISOString(); // Check last 1 minute
                    fetchMessages();
                }
            });
        });

        // Initial fetch
        setTimeout(fetchMessages, 1000);
    </script>
</x-layout-dashboard>
