<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class ChatModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureChatTable();
        $this->ensureCompanyGroup();
        $this->syncDepartmentGroups();
    }

    private function ensureChatTable(): void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS chat_groups (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                group_name VARCHAR(150) NOT NULL,
                department_id INT NULL,
                is_company_group TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_department_chat_group (company_id, department_id),
                INDEX idx_chat_group_company (company_id),
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )'
        );
        $this->query('ALTER TABLE chat_groups ADD COLUMN IF NOT EXISTS is_company_group TINYINT(1) NOT NULL DEFAULT 0 AFTER department_id');
        $this->query('CREATE INDEX IF NOT EXISTS idx_chat_group_company_type ON chat_groups (company_id, is_company_group)');
        $this->query(
            'CREATE TABLE IF NOT EXISTS chat_group_members (
                group_id INT NOT NULL,
                user_id INT NOT NULL,
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (group_id, user_id),
                FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS chat_messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                sender_id INT NOT NULL,
                recipient_id INT NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_chat_conversation (company_id, sender_id, recipient_id, created_at),
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );
        $this->query('ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message');
        $this->query('ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS group_id INT NULL AFTER recipient_id');
        $this->query('CREATE INDEX IF NOT EXISTS idx_chat_messages_group ON chat_messages (group_id, created_at)');
        $this->query(
            'CREATE TABLE IF NOT EXISTS chat_message_files (
                id INT PRIMARY KEY AUTO_INCREMENT,
                message_id INT NOT NULL,
                company_id INT NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                file_type VARCHAR(150) NOT NULL,
                file_size INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_chat_message_file (message_id),
                INDEX idx_chat_file_company (company_id),
                FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS chat_group_message_reads (
                message_id INT NOT NULL,
                user_id INT NOT NULL,
                read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (message_id, user_id),
                FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );
    }

    public function getColleaguesWithHistory(int $currentUserId): array
    {
        $stmt = $this->query(
            'SELECT u.id, u.name, u.email, e.department, e.position AS job_title,
                    MAX(cm.created_at) AS last_message_at
             FROM users u
             LEFT JOIN employees e ON e.id = u.employee_id
             INNER JOIN chat_messages cm ON cm.company_id = u.company_id
                 AND ((cm.sender_id = ? AND cm.recipient_id = u.id) OR (cm.recipient_id = ? AND cm.sender_id = u.id))
             WHERE u.company_id = ? AND u.id <> ? AND (e.id IS NULL OR e.status = "active")
             GROUP BY u.id, u.name, u.email, e.department, e.position
             ORDER BY last_message_at DESC, u.name ASC',
            [$currentUserId, $currentUserId, $this->currentCompanyId(), $currentUserId]
        );
        return $stmt->fetchAll();
    }

    public function searchColleagues(int $currentUserId, string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return [];
        }

        $like = '%' . $search . '%';
        $stmt = $this->query(
            'SELECT u.id, u.name, u.email, e.department, e.position AS job_title
             FROM users u
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE u.company_id = ? AND u.id <> ? AND (e.id IS NULL OR e.status = "active")
               AND (u.name LIKE ? OR u.email LIKE ? OR e.department LIKE ? OR e.position LIKE ?)
             ORDER BY u.name ASC
             LIMIT 20',
            [$this->currentCompanyId(), $currentUserId, $like, $like, $like, $like]
        );
        return $stmt->fetchAll();
    }

    public function getMessagesSince(int $currentUserId, int $colleagueId, int $afterId = 0): array
    {
        $stmt = $this->query(
                'SELECT cm.id, cm.sender_id, cm.recipient_id, cm.message, cm.created_at, u.name AS sender_name,
                    f.id AS file_id, f.original_name, f.file_type, f.file_size
             FROM chat_messages cm
             INNER JOIN users u ON u.id = cm.sender_id
                 LEFT JOIN chat_message_files f ON f.message_id = cm.id
             WHERE cm.company_id = ? AND cm.id > ?
               AND ((cm.sender_id = ? AND cm.recipient_id = ?) OR (cm.sender_id = ? AND cm.recipient_id = ?))
             ORDER BY cm.created_at ASC, cm.id ASC',
            [$this->currentCompanyId(), $afterId, $currentUserId, $colleagueId, $colleagueId, $currentUserId]
        );
        return $stmt->fetchAll();
    }

    public function getColleague(int $currentUserId, int $colleagueId): ?array
    {
        $stmt = $this->query(
            'SELECT u.id, u.name, u.email, e.department, e.position AS job_title
             FROM users u
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE u.company_id = ? AND u.id = ? AND u.id <> ? AND (e.id IS NULL OR e.status = "active")
             LIMIT 1',
            [$this->currentCompanyId(), $colleagueId, $currentUserId]
        );
        $colleague = $stmt->fetch();
        return $colleague ?: null;
    }

    public function getUnreadCount(int $userId): int
    {
        $directCount = (int)$this->query(
            'SELECT COUNT(*) FROM chat_messages WHERE company_id = ? AND recipient_id = ? AND is_read = 0',
            [$this->currentCompanyId(), $userId]
        )->fetchColumn();
        return $directCount + $this->getUnreadGroupCount($userId);
    }

    public function markConversationRead(int $userId, int $colleagueId): void
    {
        $this->query(
            'UPDATE chat_messages SET is_read = 1
             WHERE company_id = ? AND recipient_id = ? AND sender_id = ? AND is_read = 0',
            [$this->currentCompanyId(), $userId, $colleagueId]
        );
    }

    public function getMessages(int $currentUserId, int $colleagueId): array
    {
        return $this->getMessagesSince($currentUserId, $colleagueId);
    }

    public function sendMessage(int $senderId, int $recipientId, string $message, ?array $attachment = null): void
    {
        $message = trim($message);
        if ($recipientId <= 0 || ($message === '' && $attachment === null) || strlen($message) > 5000) {
            throw new InvalidArgumentException('Choose a colleague and enter a message up to 5,000 characters, or attach a file.');
        }

        $recipientExists = $this->query(
            'SELECT 1 FROM users WHERE id = ? AND company_id = ? LIMIT 1',
            [$recipientId, $this->currentCompanyId()]
        )->fetchColumn();
        if (!$recipientExists || $recipientId === $senderId) {
            throw new InvalidArgumentException('That colleague is not available for chat.');
        }

        $this->insertMessageWithAttachment(
            $attachment,
            'INSERT INTO chat_messages (company_id, sender_id, recipient_id, message) VALUES (?, ?, ?, ?)',
            [$this->currentCompanyId(), $senderId, $recipientId, $message]
        );
    }

    public function getGroupsForUser(int $userId): array
    {
        $isTopLevel = $this->isTopLevelUser($userId);
        $stmt = $this->query(
            'SELECT cg.id, cg.group_name, cg.department_id, d.name AS department_name,
                    MAX(cm.created_at) AS last_message_at
             FROM chat_groups cg
             LEFT JOIN chat_group_members cgm ON cgm.group_id = cg.id AND cgm.user_id = ?
             LEFT JOIN departments d ON d.id = cg.department_id AND d.company_id = cg.company_id
             LEFT JOIN chat_messages cm ON cm.group_id = cg.id AND cm.company_id = cg.company_id
             WHERE cg.company_id = ? AND (? = 1 OR cgm.user_id IS NOT NULL)
             GROUP BY cg.id, cg.group_name, cg.department_id, d.name
             ORDER BY last_message_at DESC, cg.group_name ASC',
            [$userId, $this->currentCompanyId(), $isTopLevel ? 1 : 0]
        );
        return $stmt->fetchAll();
    }

    public function getGroup(int $groupId, int $userId): ?array
    {
        $isTopLevel = $this->isTopLevelUser($userId);
        $stmt = $this->query(
            'SELECT cg.id, cg.group_name, cg.department_id, d.name AS department_name
             FROM chat_groups cg
             LEFT JOIN chat_group_members cgm ON cgm.group_id = cg.id AND cgm.user_id = ?
             LEFT JOIN departments d ON d.id = cg.department_id AND d.company_id = cg.company_id
             WHERE cg.id = ? AND cg.company_id = ? AND (? = 1 OR cgm.user_id IS NOT NULL) LIMIT 1',
            [$userId, $groupId, $this->currentCompanyId(), $isTopLevel ? 1 : 0]
        );
        $group = $stmt->fetch();
        return $group ?: null;
    }

    public function getGroupMembers(int $groupId, int $viewerId): array
    {
        if ($this->getGroup($groupId, $viewerId) === null) {
            return [];
        }
        return $this->query(
            'SELECT u.id, u.name, u.email, e.department, e.position AS job_title
             FROM chat_group_members cgm INNER JOIN users u ON u.id = cgm.user_id
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE cgm.group_id = ? AND u.company_id = ? ORDER BY u.name ASC',
            [$groupId, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function getAvailableGroupMembers(int $groupId, int $viewerId): array
    {
        if ($this->getGroup($groupId, $viewerId) === null) {
            return [];
        }
        return $this->query(
            'SELECT u.id, u.name, u.email, e.department, e.position AS job_title
             FROM users u LEFT JOIN employees e ON e.id = u.employee_id
             WHERE u.company_id = ? AND (e.id IS NULL OR e.status = "active")
               AND NOT EXISTS (SELECT 1 FROM chat_group_members cgm WHERE cgm.group_id = ? AND cgm.user_id = u.id)
             ORDER BY u.name ASC',
            [$this->currentCompanyId(), $groupId]
        )->fetchAll();
    }

    public function canManageGroup(int $groupId, int $userId): bool
    {
        $group = $this->query('SELECT department_id FROM chat_groups WHERE id = ? AND company_id = ? LIMIT 1', [$groupId, $this->currentCompanyId()])->fetch();
        if (!$group) {
            return false;
        }
        if ($this->isTopLevelUser($userId)) {
            return true;
        }
        return $group['department_id'] !== null && (bool)$this->query(
            'SELECT 1 FROM departments d INNER JOIN employees e ON e.id = d.head_employee_id
             INNER JOIN users u ON u.employee_id = e.id AND u.company_id = e.company_id
             WHERE d.id = ? AND d.company_id = ? AND u.id = ? LIMIT 1',
            [(int)$group['department_id'], $this->currentCompanyId(), $userId]
        )->fetchColumn();
    }

    public function addGroupMember(int $groupId, int $memberId, int $managerId): void
    {
        if (!$this->canManageGroup($groupId, $managerId)) {
            throw new InvalidArgumentException('You do not have permission to manage this group.');
        }
        if (!$this->query('SELECT 1 FROM users WHERE id = ? AND company_id = ? LIMIT 1', [$memberId, $this->currentCompanyId()])->fetchColumn()) {
            throw new InvalidArgumentException('The selected colleague is not available.');
        }
        $this->query('INSERT IGNORE INTO chat_group_members (group_id, user_id) SELECT ?, id FROM users WHERE id = ? AND company_id = ?', [$groupId, $memberId, $this->currentCompanyId()]);
    }

    public function removeGroupMember(int $groupId, int $memberId, int $managerId): void
    {
        if (!$this->canManageGroup($groupId, $managerId)) {
            throw new InvalidArgumentException('You do not have permission to manage this group.');
        }
        $departmentHead = $this->query(
            'SELECT u.id FROM chat_groups cg INNER JOIN departments d ON d.id = cg.department_id
             INNER JOIN employees e ON e.id = d.head_employee_id
             INNER JOIN users u ON u.employee_id = e.id AND u.company_id = e.company_id
             WHERE cg.id = ? AND cg.company_id = ? LIMIT 1',
            [$groupId, $this->currentCompanyId()]
        )->fetchColumn();
        if ((int)$departmentHead === $memberId) {
            throw new InvalidArgumentException('The department head must remain a member of the department group.');
        }
        $this->query('DELETE FROM chat_group_members WHERE group_id = ? AND user_id = ?', [$groupId, $memberId]);
    }

    public function getGroupMessages(int $groupId, int $userId, int $afterId = 0): array
    {
        if ($this->getGroup($groupId, $userId) === null) {
            return [];
        }
        return $this->query(
                'SELECT cm.id, cm.sender_id, cm.message, cm.created_at, u.name AS sender_name,
                    f.id AS file_id, f.original_name, f.file_type, f.file_size
             FROM chat_messages cm INNER JOIN users u ON u.id = cm.sender_id
                 LEFT JOIN chat_message_files f ON f.message_id = cm.id
             WHERE cm.company_id = ? AND cm.group_id = ? AND cm.id > ?
             ORDER BY cm.created_at ASC, cm.id ASC',
            [$this->currentCompanyId(), $groupId, $afterId]
        )->fetchAll();
    }

    public function sendGroupMessage(int $senderId, int $groupId, string $message, ?array $attachment = null): void
    {
        $message = trim($message);
        if (($message === '' && $attachment === null) || strlen($message) > 5000 || $this->getGroup($groupId, $senderId) === null) {
            throw new InvalidArgumentException('Choose a valid group and enter a message up to 5,000 characters, or attach a file.');
        }
        $this->insertMessageWithAttachment(
            $attachment,
            'INSERT INTO chat_messages (company_id, sender_id, recipient_id, group_id, message) VALUES (?, ?, ?, ?, ?)',
            [$this->currentCompanyId(), $senderId, $senderId, $groupId, $message]
        );
    }

    private function insertMessageWithAttachment(?array $attachment, string $sql, array $params): void
    {
        $this->db->beginTransaction();
        try {
            $this->query($sql, $params);
            $messageId = (int)$this->db->lastInsertId();
            if ($attachment !== null) {
                $this->query(
                    'INSERT INTO chat_message_files (message_id, company_id, original_name, stored_name, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)',
                    [$messageId, $this->currentCompanyId(), $attachment['original_name'], $attachment['stored_name'], $attachment['file_type'], $attachment['file_size']]
                );
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function getAttachment(int $fileId, int $userId): ?array
    {
        $file = $this->query(
            'SELECT f.*, cm.sender_id, cm.recipient_id, cm.group_id
             FROM chat_message_files f INNER JOIN chat_messages cm ON cm.id = f.message_id
             LEFT JOIN chat_group_members cgm ON cgm.group_id = cm.group_id AND cgm.user_id = ?
             WHERE f.id = ? AND f.company_id = ?
               AND (cm.recipient_id = ? OR cm.sender_id = ? OR cgm.user_id IS NOT NULL
                    OR (? = 1 AND cm.group_id IS NOT NULL)) LIMIT 1',
            [$userId, $fileId, $this->currentCompanyId(), $userId, $userId, $this->isTopLevelUser($userId) ? 1 : 0]
        )->fetch();
        return $file ?: null;
    }

    private function isTopLevelUser(int $userId): bool
    {
        return (bool)$this->query(
            'SELECT 1 FROM users u
             WHERE u.id = ? AND u.company_id = ? AND u.role_id IS NOT NULL
               AND NOT EXISTS (
                   SELECT 1 FROM workflow_role_links wrl
                   WHERE wrl.company_id = u.company_id AND wrl.role_id = u.role_id AND wrl.parent_role_id IS NOT NULL
               )
             LIMIT 1',
            [$userId, $this->currentCompanyId()]
        )->fetchColumn();
    }

    public function markGroupRead(int $groupId, int $userId): void
    {
        $this->query(
            'INSERT IGNORE INTO chat_group_message_reads (message_id, user_id)
             SELECT cm.id, ? FROM chat_messages cm
             WHERE cm.company_id = ? AND cm.group_id = ? AND cm.sender_id <> ?',
            [$userId, $this->currentCompanyId(), $groupId, $userId]
        );
    }

    public function getUnreadGroupCount(int $userId): int
    {
        $isTopLevel = $this->isTopLevelUser($userId);
        return (int)$this->query(
            'SELECT COUNT(*) FROM chat_messages cm
             LEFT JOIN chat_group_members cgm ON cgm.group_id = cm.group_id AND cgm.user_id = ?
             LEFT JOIN chat_group_message_reads cgr ON cgr.message_id = cm.id AND cgr.user_id = ?
             WHERE cm.company_id = ? AND cm.group_id IS NOT NULL AND cm.sender_id <> ? AND cgr.message_id IS NULL
               AND (? = 1 OR cgm.user_id IS NOT NULL)',
            [$userId, $userId, $this->currentCompanyId(), $userId, $isTopLevel ? 1 : 0]
        )->fetchColumn();
    }

    public function createGroup(int $creatorId, string $name, ?int $departmentId = null): int
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 150) {
            throw new InvalidArgumentException('A group name between 1 and 150 characters is required.');
        }
        $this->query('INSERT INTO chat_groups (company_id, group_name, department_id, created_by) VALUES (?, ?, ?, ?)', [$this->currentCompanyId(), $name, $departmentId, $creatorId]);
        $groupId = (int)$this->db->lastInsertId();
        $this->query('INSERT IGNORE INTO chat_group_members (group_id, user_id) SELECT ?, id FROM users WHERE company_id = ?', [$groupId, $this->currentCompanyId()]);
        return $groupId;
    }

    public function ensureDepartmentGroup(int $departmentId, string $departmentName): void
    {
        $companyId = $this->currentCompanyId();
        $existingGroupId = (int)$this->query('SELECT id FROM chat_groups WHERE company_id = ? AND department_id = ? LIMIT 1', [$companyId, $departmentId])->fetchColumn();
        $this->query('INSERT IGNORE INTO chat_groups (company_id, group_name, department_id) VALUES (?, ?, ?)', [$companyId, $departmentName . ' Department', $departmentId]);
        $groupId = (int)$this->query('SELECT id FROM chat_groups WHERE company_id = ? AND department_id = ? LIMIT 1', [$companyId, $departmentId])->fetchColumn();
        if ($existingGroupId > 0) {
            return;
        }
        $this->query(
            'INSERT IGNORE INTO chat_group_members (group_id, user_id)
             SELECT ?, u.id FROM users u INNER JOIN employees e ON e.id = u.employee_id
             WHERE u.company_id = ? AND e.company_id = ? AND e.department = ? AND e.status = "active"',
            [$groupId, $companyId, $companyId, $departmentName]
        );
    }

    public function ensureCompanyGroup(): void
    {
        $companyId = $this->currentCompanyId();
        $this->query(
            'INSERT IGNORE INTO chat_groups (company_id, group_name, is_company_group) VALUES (?, ?, 1)',
            [$companyId, 'All Company Employees']
        );
        $groupId = (int)$this->query('SELECT id FROM chat_groups WHERE company_id = ? AND is_company_group = 1 LIMIT 1', [$companyId])->fetchColumn();
        if ($groupId <= 0) {
            return;
        }
        $this->query(
            'INSERT IGNORE INTO chat_group_members (group_id, user_id)
             SELECT ?, u.id FROM users u INNER JOIN employees e ON e.id = u.employee_id
             WHERE u.company_id = ? AND e.company_id = ? AND e.status = "active"',
            [$groupId, $companyId, $companyId]
        );
        $this->query(
            'DELETE cgm FROM chat_group_members cgm
             INNER JOIN chat_groups cg ON cg.id = cgm.group_id
             INNER JOIN users u ON u.id = cgm.user_id
             LEFT JOIN employees e ON e.id = u.employee_id AND e.company_id = u.company_id AND e.status = "active"
             WHERE cg.id = ? AND e.id IS NULL',
            [$groupId]
        );
    }

    public function deleteDepartmentGroup(int $departmentId): void
    {
        $this->query('DELETE FROM chat_groups WHERE company_id = ? AND department_id = ?', [$this->currentCompanyId(), $departmentId]);
    }

    private function syncDepartmentGroups(): void
    {
        $departments = $this->query('SELECT id, name FROM departments WHERE company_id = ?', [$this->currentCompanyId()])->fetchAll();
        foreach ($departments as $department) {
            $this->ensureDepartmentGroup((int)$department['id'], (string)$department['name']);
        }
    }
}
