export type TaskStatus   = 'todo' | 'in_progress' | 'done'
export type TaskPriority = 'low' | 'medium' | 'high'

export interface User {
  id:    number
  name:  string
  email: string
}

// ── Stats types ─────────────────────────────────────────────────────

export interface ProjectStat {
  id:              number
  name:            string
  total_tasks:     number
  done_tasks:      number
  completion_rate: number
}

export interface DashboardStats {
  total_tasks:       number
  completed_tasks:   number
  todo_tasks:        number
  in_progress_tasks: number
  overdue_tasks:     number
  completion_rate:   number
  total_projects:    number
  project_stats:     ProjectStat[]
}

export interface Project {
  id:          number
  name:        string
  description: string | null
  user_id:     number
  created_at:  string
  updated_at:  string
  owner?:      User
  tasks?:      Task[]
  tasks_count?: number
}

export interface Task {
  id:          number
  title:       string
  description: string | null
  status:      TaskStatus
  priority:    TaskPriority
  due_date:    string | null
  is_overdue:  boolean
  project_id:  number
  created_by:  number
  created_at:  string
  updated_at:  string
  project?:    Project
  assignees?:  User[]
  creator?:    User
}

export interface CursorPaginatedResponse<T> {
  data: T[]
  meta: {
    next_cursor: string | null
    prev_cursor: string | null
    per_page:    number
    path:        string
  }
  links: {
    first: string | null
    last:  string | null
    prev:  string | null
    next:  string | null
  }
}

export interface ValidationError {
  message: string
  errors:  Record<string, string[]>
}

export interface ApiResponse<T> {
  data: T
}

export interface TaskFilters {
  search:   string
  status:   TaskStatus | ''
  priority: TaskPriority | ''
  sort:     string
}

// ── Auth types ──────────────────────────────────────────────────────

export interface AuthUser {
  id:    number
  name:  string
  email: string
}

export interface LoginPayload {
  email:    string
  password: string
}

export interface RegisterPayload {
  name:                  string
  email:                 string
  password:              string
  password_confirmation: string
}

export interface AuthResponse {
  data: {
    user:         AuthUser
    access_token: string
    token_type:   string
  }
}

// ── Dependency graph types ───────────────────────────────────────────

export interface TaskGraphNode {
  id:           string
  title:        string
  status:       TaskStatus
  priority:     TaskPriority
  is_overdue:   boolean
  dependencies: string[]  // array of task IDs this task depends on
}

export interface DependencyEdge {
  id:     string
  source: string  // depends_on_task_id (must complete first)
  target: string  // task_id (waiting for the dependency)
}