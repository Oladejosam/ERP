<style>
    .chat-shell { min-height: 68vh; }
    .chat-people { max-height: 62vh; overflow-y: auto; }
    .chat-thread { min-height: 48vh; max-height: 58vh; overflow-y: auto; background: #f8fafc; }
    .chat-message { max-width: 78%; }
    .chat-message.mine { margin-left: auto; }
    .chat-attach-link { width: 36px; height: 36px; border: 0; background: transparent; color: #198754; line-height: 1; }
    .chat-attach-link img { width: 20px; height: 20px; object-fit: contain; display: block; }
    .chat-attach-link:hover, .chat-attach-link:focus { color: #146c43; }
    .chat-attach-name { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .chat-voice-button { width: 36px; height: 36px; }
    .chat-voice-status { min-width: 72px; }
</style>

<div class="card shadow-sm border-0 chat-shell">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center border-bottom p-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-chat-dots text-primary me-2"></i>Team Chat</h4>
                <p class="text-muted mb-0">Have a quick conversation with colleagues in your company.</p>
            </div>
        </div>

        <?php if (!empty($_SESSION['chat_flash'])): ?>
            <div class="alert alert-danger mx-4 mt-3 mb-0"><?php echo htmlspecialchars($_SESSION['chat_flash']); unset($_SESSION['chat_flash']); ?></div>
        <?php endif; ?>

        <div class="row g-0">
            <aside class="col-lg-4 border-end">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="small text-uppercase fw-semibold text-muted">Recent conversations</span>
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#newChatModal"><i class="bi bi-plus-lg me-1"></i>New chat</button>
                    <?php if (!empty($canCreateGroup)): ?><button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#newGroupModal"><i class="bi bi-people me-1"></i>Group</button><?php endif; ?>
                </div>
                <div class="list-group list-group-flush chat-people" id="chatPeople">
                    <?php foreach (($conversations ?? []) as $conversation): ?>
                        <?php $conversationData = $conversation['data']; $isGroup = $conversation['type'] === 'group'; $isSelected = $isGroup ? ($selectedGroup && (int)$conversationData['id'] === (int)$selectedGroup['id']) : ($selectedColleague && (int)$conversationData['id'] === (int)$selectedColleague['id']); ?>
                        <a class="list-group-item list-group-item-action py-3 <?php echo $isSelected ? 'active' : ''; ?>" href="/ERP/public/modules/chat?<?php echo $isGroup ? 'group=' . (int)$conversationData['id'] : 'with=' . (int)$conversationData['id']; ?>">
                            <div class="d-flex align-items-center gap-3">
                                <span class="rounded-circle <?php echo $isGroup ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary'; ?> d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;"><i class="bi <?php echo $isGroup ? 'bi-people' : 'bi-person'; ?>"></i></span>
                                <span class="min-w-0"><strong class="d-block text-truncate"><?php echo htmlspecialchars((string)($isGroup ? $conversationData['group_name'] : $conversationData['name'])); ?></strong><small class="<?php echo $isSelected ? 'text-white-50' : 'text-muted'; ?>"><?php echo htmlspecialchars((string)($isGroup ? ($conversationData['department_name'] ?: 'Group chat') : ($conversationData['department'] ?: 'Company colleague'))); ?></small></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($conversations)): ?><div class="p-4 text-center text-muted">No conversations yet. Start one with New chat.</div><?php endif; ?>
                </div>
            </aside>

            <section class="col-lg-8 d-flex flex-column">
                <?php if ($selectedGroup): ?>
                    <div class="p-3 border-bottom d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;"><i class="bi bi-people"></i></span>
                        <div class="flex-grow-1"><h5 class="mb-0"><?php echo htmlspecialchars((string)$selectedGroup['group_name']); ?></h5><small class="text-muted"><?php echo htmlspecialchars((string)($selectedGroup['department_name'] ?: 'Company group')); ?> · <?php echo count($groupMembers ?? []); ?> members</small></div>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#groupMembersModal"><i class="bi bi-people me-1"></i>Members</button>
                    </div>
                    <div class="chat-thread p-4" id="chatThread" data-current-user-id="<?php echo (int)$currentUserId; ?>" data-group-id="<?php echo (int)$selectedGroup['id']; ?>">
                        <?php if (empty($groupMessages)): ?><div class="text-center text-muted py-5">No messages yet. Start the conversation.</div><?php endif; ?>
                        <?php foreach (($groupMessages ?? []) as $message): ?>
                            <?php $isMine = (int)$message['sender_id'] === (int)$currentUserId; ?>
                            <div class="chat-message <?php echo $isMine ? 'mine' : ''; ?> mb-3" data-message-id="<?php echo (int)$message['id']; ?>">
                                <div class="small text-muted mb-1 <?php echo $isMine ? 'text-end' : ''; ?>"><?php echo $isMine ? 'You' : htmlspecialchars((string)$message['sender_name']); ?> · <?php echo htmlspecialchars(date('M j, g:i a', strtotime((string)$message['created_at']))); ?></div>
                                <div class="rounded-3 p-3 <?php echo $isMine ? 'bg-success text-white' : 'bg-white border'; ?>"><?php echo nl2br(htmlspecialchars((string)$message['message'])); ?><?php if (!empty($message['file_id'])): ?><?php if (strpos((string)$message['file_type'], 'audio/') === 0): ?><audio class="d-block mt-2 mw-100" controls preload="metadata" src="/ERP/public/modules/chat/file?id=<?php echo (int)$message['file_id']; ?>"></audio><?php else: ?><a class="d-block mt-2 <?php echo $isMine ? 'text-white' : 'text-primary'; ?>" href="/ERP/public/modules/chat/file?id=<?php echo (int)$message['file_id']; ?>"><i class="bi bi-paperclip me-1"></i><?php echo htmlspecialchars((string)$message['original_name']); ?> <small>(<?php echo number_format(((int)$message['file_size']) / 1048576, 2); ?> MB)</small></a><?php endif; ?><?php endif; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form class="p-3 border-top" method="post" action="/ERP/public/modules/chat/send" enctype="multipart/form-data">
                        <input type="hidden" name="group_id" value="<?php echo (int)$selectedGroup['id']; ?>">
                        <div class="input-group"><textarea class="form-control" name="message" rows="2" maxlength="5000" placeholder="Write to the group..."></textarea><input class="d-none chat-file-input" id="groupChatFile" type="file" name="chat_file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip,.doc,.docx,.xls,.xlsx,audio/*"><label class="chat-attach-link d-inline-flex align-items-center justify-content-center border border-start-0" for="groupChatFile" title="Attach a file" aria-label="Attach a file"><img src="/ERP/public/assets/chat-attach.png" alt=""></label><button class="btn btn-outline-success chat-voice-button" type="button" data-voice-record title="Record voice note" aria-label="Record voice note"><i class="bi bi-mic"></i></button><span class="chat-voice-status small text-muted d-flex align-items-center justify-content-center" data-voice-status></span><button class="btn btn-success px-4" type="submit"><i class="bi bi-send me-1"></i>Send</button></div>
                        <div class="chat-attach-name small text-muted mt-1 ms-2" data-file-name-for="groupChatFile"></div>
                    </form>
                <?php elseif ($selectedColleague): ?>
                    <div class="p-3 border-bottom d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;"><i class="bi bi-person"></i></span>
                        <div><h5 class="mb-0"><?php echo htmlspecialchars((string)$selectedColleague['name']); ?></h5><small class="text-muted"><?php echo htmlspecialchars((string)($selectedColleague['job_title'] ?: $selectedColleague['department'] ?: 'Company colleague')); ?></small></div>
                    </div>
                    <div class="chat-thread p-4" id="chatThread" data-current-user-id="<?php echo (int)$currentUserId; ?>" data-colleague-id="<?php echo (int)$selectedColleague['id']; ?>">
                        <?php if (empty($messages)): ?><div class="text-center text-muted py-5">No messages yet. Start the conversation.</div><?php endif; ?>
                        <?php foreach (($messages ?? []) as $message): ?>
                            <?php $isMine = (int)$message['sender_id'] === (int)$currentUserId; ?>
                            <div class="chat-message <?php echo $isMine ? 'mine' : ''; ?> mb-3" data-message-id="<?php echo (int)$message['id']; ?>">
                                <div class="small text-muted mb-1 <?php echo $isMine ? 'text-end' : ''; ?>"><?php echo $isMine ? 'You' : htmlspecialchars((string)$message['sender_name']); ?> · <?php echo htmlspecialchars(date('M j, g:i a', strtotime((string)$message['created_at']))); ?></div>
                                <div class="rounded-3 p-3 <?php echo $isMine ? 'bg-primary text-white' : 'bg-white border'; ?>"><?php echo nl2br(htmlspecialchars((string)$message['message'])); ?><?php if (!empty($message['file_id'])): ?><?php if (strpos((string)$message['file_type'], 'audio/') === 0): ?><audio class="d-block mt-2 mw-100" controls preload="metadata" src="/ERP/public/modules/chat/file?id=<?php echo (int)$message['file_id']; ?>"></audio><?php else: ?><a class="d-block mt-2 <?php echo $isMine ? 'text-white' : 'text-primary'; ?>" href="/ERP/public/modules/chat/file?id=<?php echo (int)$message['file_id']; ?>"><i class="bi bi-paperclip me-1"></i><?php echo htmlspecialchars((string)$message['original_name']); ?> <small>(<?php echo number_format(((int)$message['file_size']) / 1048576, 2); ?> MB)</small></a><?php endif; ?><?php endif; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form class="p-3 border-top" method="post" action="/ERP/public/modules/chat/send" enctype="multipart/form-data">
                        <input type="hidden" name="recipient_id" value="<?php echo (int)$selectedColleague['id']; ?>">
                        <div class="input-group">
                            <textarea class="form-control" name="message" rows="2" maxlength="5000" placeholder="Write a message..."></textarea>
                            <input class="d-none chat-file-input" id="directChatFile" type="file" name="chat_file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip,.doc,.docx,.xls,.xlsx,audio/*">
                            <label class="chat-attach-link d-inline-flex align-items-center justify-content-center border border-start-0" for="directChatFile" title="Attach a file" aria-label="Attach a file"><img src="/ERP/public/assets/chat-attach.png" alt=""></label>
                            <button class="btn btn-outline-primary chat-voice-button" type="button" data-voice-record title="Record voice note" aria-label="Record voice note"><i class="bi bi-mic"></i></button>
                            <span class="chat-voice-status small text-muted d-flex align-items-center justify-content-center" data-voice-status></span>
                            <button class="btn btn-primary px-4" type="submit"><i class="bi bi-send me-1"></i>Send</button>
                        </div>
                        <div class="chat-attach-name small text-muted mt-1 ms-2" data-file-name-for="directChatFile"></div>
                    </form>
                <?php else: ?>
                    <div class="flex-grow-1 d-flex align-items-center justify-content-center text-center p-5 text-muted"><div><i class="bi bi-chat-square-text display-4 d-block mb-3"></i>Select a colleague to start chatting.</div></div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newChatModalLabel">Start a new chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="newChatSearch">Search colleagues</label>
                <input class="form-control" id="newChatSearch" type="search" autocomplete="off" placeholder="Name, email, department, or position">
                <div class="list-group mt-3" id="newChatResults">
                    <div class="text-muted small">Type at least two characters to search.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($selectedGroup): ?>
<div class="modal fade" id="groupMembersModal" tabindex="-1" aria-labelledby="groupMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="groupMembersModalLabel">Group members</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="list-group mb-4">
                    <?php foreach (($groupMembers ?? []) as $member): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between">
                            <span><strong><?php echo htmlspecialchars((string)$member['name']); ?></strong><small class="d-block text-muted"><?php echo htmlspecialchars((string)($member['department'] ?: $member['email'])); ?></small></span>
                            <?php if (!empty($canManageSelectedGroup)): ?><form method="post" action="/ERP/public/modules/chat/group/member/remove"><input type="hidden" name="group_id" value="<?php echo (int)$selectedGroup['id']; ?>"><input type="hidden" name="user_id" value="<?php echo (int)$member['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Remove member"><i class="bi bi-person-dash"></i></button></form><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($canManageSelectedGroup)): ?>
                    <form class="row g-2 align-items-end" method="post" action="/ERP/public/modules/chat/group/member/add">
                        <input type="hidden" name="group_id" value="<?php echo (int)$selectedGroup['id']; ?>">
                        <div class="col"><label class="form-label" for="groupMemberSelect">Add colleague</label><select class="form-select" id="groupMemberSelect" name="user_id" required><option value="">Choose a colleague</option><?php foreach (($availableGroupMembers ?? []) as $member): ?><option value="<?php echo (int)$member['id']; ?>"><?php echo htmlspecialchars((string)$member['name']); ?><?php if (!empty($member['department'])): ?> - <?php echo htmlspecialchars((string)$member['department']); ?><?php endif; ?></option><?php endforeach; ?></select></div>
                        <div class="col-auto"><button class="btn btn-primary" type="submit"><i class="bi bi-person-plus me-1"></i>Add member</button></div>
                    </form>
                <?php else: ?><small class="text-muted">Only the department head or highest-level organogram role can change membership.</small><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canCreateGroup)): ?>
<div class="modal fade" id="newGroupModal" tabindex="-1" aria-labelledby="newGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="newGroupModalLabel">Create a group chat</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="post" action="/ERP/public/modules/chat/group/create">
                <div class="modal-body"><label class="form-label" for="groupName">Group name</label><input class="form-control" id="groupName" name="group_name" maxlength="150" placeholder="e.g. Project Leadership" required><small class="text-muted">All active company users will be added to this group.</small></div>
                <div class="modal-footer"><button class="btn btn-success" type="submit"><i class="bi bi-people me-1"></i>Create group</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(() => {
    const baseUrl = '/ERP/public/modules/chat';
    const thread = document.getElementById('chatThread');
    if (thread) thread.scrollTop = thread.scrollHeight;

    const search = document.getElementById('newChatSearch');
    const results = document.getElementById('newChatResults');
    document.querySelectorAll('.chat-file-input').forEach((input) => {
        input.addEventListener('change', () => {
            const name = document.querySelector(`[data-file-name-for="${input.id}"]`);
            if (name) name.textContent = input.files.length ? input.files[0].name : '';
        });
    });
    document.querySelectorAll('[data-voice-record]').forEach((button) => {
        const form = button.closest('form');
        const fileInput = form ? form.querySelector('.chat-file-input') : null;
        const status = form ? form.querySelector('[data-voice-status]') : null;
        let recorder = null;
        let stream = null;
        let chunks = [];
        let startedAt = 0;
        let timer = null;

        const setStatus = (text) => {
            if (status) status.textContent = text;
        };
        const formatDuration = (seconds) => `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        const stopRecording = () => {
            if (recorder && recorder.state === 'recording') recorder.stop();
            if (stream) stream.getTracks().forEach((track) => track.stop());
            if (timer) window.clearInterval(timer);
            button.classList.remove('btn-danger');
            button.classList.add(button.closest('form')?.querySelector('[name="group_id"]') ? 'btn-outline-success' : 'btn-outline-primary');
            button.innerHTML = '<i class="bi bi-mic"></i>';
            button.title = 'Record voice note';
            button.setAttribute('aria-label', 'Record voice note');
        };

        button.addEventListener('click', async () => {
            if (recorder && recorder.state === 'recording') {
                stopRecording();
                return;
            }
            if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
                setStatus('Voice recording is not supported by this browser.');
                return;
            }
            try {
                stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const mimeType = ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus', 'audio/mp4'].find((type) => MediaRecorder.isTypeSupported(type)) || '';
                recorder = new MediaRecorder(stream, mimeType ? { mimeType } : undefined);
                chunks = [];
                recorder.addEventListener('dataavailable', (event) => {
                    if (event.data.size > 0) chunks.push(event.data);
                });
                recorder.addEventListener('stop', () => {
                    const type = recorder.mimeType || 'audio/webm';
                    const extension = type.includes('ogg') ? 'ogg' : (type.includes('mp4') ? 'm4a' : 'webm');
                    const voiceFile = new File([new Blob(chunks, { type })], `voice-note-${Date.now()}.${extension}`, { type });
                    const transfer = new DataTransfer();
                    transfer.items.add(voiceFile);
                    fileInput.files = transfer.files;
                    const name = form.querySelector(`[data-file-name-for="${fileInput.id}"]`);
                    if (name) name.textContent = voiceFile.name;
                    setStatus('Voice note ready');
                });
                recorder.start();
                startedAt = Date.now();
                button.classList.remove('btn-outline-success', 'btn-outline-primary');
                button.classList.add('btn-danger');
                button.innerHTML = '<i class="bi bi-stop-fill"></i>';
                button.title = 'Stop recording';
                button.setAttribute('aria-label', 'Stop recording');
                setStatus('00:00');
                timer = window.setInterval(() => {
                    const elapsed = Math.floor((Date.now() - startedAt) / 1000);
                    setStatus(formatDuration(elapsed));
                    if (elapsed >= 120) stopRecording();
                }, 1000);
            } catch (error) {
                if (stream) stream.getTracks().forEach((track) => track.stop());
                setStatus('Microphone access was not granted.');
            }
        });
    });
    let searchTimer;
    if (search && results) search.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        const term = search.value.trim();
        if (term.length < 2) {
            results.innerHTML = '<div class="text-muted small">Type at least two characters to search.</div>';
            return;
        }
        searchTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(`${baseUrl}/colleagues?search=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                results.innerHTML = '';
                if (!data.colleagues || data.colleagues.length === 0) {
                    results.innerHTML = '<div class="text-muted small">No matching colleagues found.</div>';
                    return;
                }
                data.colleagues.forEach((colleague) => {
                    const link = document.createElement('a');
                    link.className = 'list-group-item list-group-item-action';
                    link.href = `${baseUrl}?with=${encodeURIComponent(colleague.id)}`;
                    const detail = colleague.department || colleague.job_title || 'Company colleague';
                    link.textContent = `${colleague.name} - ${detail}`;
                    results.appendChild(link);
                });
            } catch (error) {
                results.innerHTML = '<div class="text-danger small">Unable to search colleagues.</div>';
            }
        }, 250);
    });

    if (thread) {
        const colleagueId = Number(thread.dataset.colleagueId);
        const groupId = Number(thread.dataset.groupId);
        const renderMessage = (message) => {
            if (thread.querySelector(`[data-message-id="${message.id}"]`)) return;
            const isMine = Number(message.sender_id) === Number(thread.dataset.currentUserId);
            const wrapper = document.createElement('div');
            wrapper.className = `chat-message ${isMine ? 'mine' : ''} mb-3`;
            wrapper.dataset.messageId = message.id;
            const meta = document.createElement('div');
            meta.className = `small text-muted mb-1 ${isMine ? 'text-end' : ''}`;
            meta.textContent = `${isMine ? 'You' : message.sender_name} - ${new Date(message.created_at.replace(' ', 'T')).toLocaleString()}`;
            const body = document.createElement('div');
            body.className = `rounded-3 p-3 ${isMine ? 'bg-primary text-white' : 'bg-white border'}`;
            body.textContent = message.message;
            if (message.file_id) {
                if (String(message.file_type || '').startsWith('audio/')) {
                    const audio = document.createElement('audio');
                    audio.className = 'd-block mt-2 mw-100';
                    audio.controls = true;
                    audio.preload = 'metadata';
                    audio.src = `${baseUrl}/file?id=${encodeURIComponent(message.file_id)}`;
                    body.appendChild(audio);
                } else {
                    const fileLink = document.createElement('a');
                    fileLink.className = `d-block mt-2 ${isMine ? 'text-white' : 'text-primary'}`;
                    fileLink.href = `${baseUrl}/file?id=${encodeURIComponent(message.file_id)}`;
                    fileLink.textContent = `Attachment: ${message.original_name}`;
                    body.appendChild(fileLink);
                }
            }
            wrapper.append(meta, body);
            const empty = thread.querySelector('.text-center.text-muted');
            if (empty) empty.remove();
            thread.appendChild(wrapper);
        };
        const pollMessages = async () => {
            const messageNodes = thread.querySelectorAll('[data-message-id]');
            const after = messageNodes.length ? Number(messageNodes[messageNodes.length - 1].dataset.messageId) : 0;
            try {
                const query = groupId > 0 ? `group=${groupId}` : `with=${colleagueId}`;
                const response = await fetch(`${baseUrl}/messages?${query}&after=${after}`, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                (data.messages || []).forEach(renderMessage);
                if ((data.messages || []).length) thread.scrollTop = thread.scrollHeight;
            } catch (error) {
                // Polling is best effort; the normal page remains usable if the request fails.
            }
        };
        window.setInterval(pollMessages, 5000);
    }
})();
</script>
