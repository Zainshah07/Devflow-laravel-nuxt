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