export type TaskStatus = 'todo' | 'in_progress' | 'done'
export type TaskPriority = 'low' | 'medium' | 'high'

export interface User {
  id: number
  name: string
  email: string
}

export interface Project {
  id: number
  name: string
  description: string | null
  user_id: number
  created_at: string
  owner?: User
  tasks?: Task[]
  tasks_count?: number
}

export interface Task {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  due_date: string | null
  is_overdue:boolean
  project_id: number
  created_by: number
  created_at: string
  project?: Project
  assignees?: User[]
  creator?: User
}

// DSA — Cursor pagination response shape:
// next_cursor is an opaque string token pointing to the last seen row.
// The API uses this to jump directly to the next page via index lookup
// instead of scanning and discarding rows with OFFSET.
export interface CursorPaginatedResponse<T> {
  data: T[]
  meta: {
    next_cursor:  string | null
    prev_cursor:  string | null
    per_page:     number
    path:         string
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
  errors: Record<string, string[]>
}

export interface ApiResponse<T> {
  data: T
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}