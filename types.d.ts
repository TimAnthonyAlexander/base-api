// Generated TypeScript definitions for BaseApi
// Do not edit manually - regenerate with: php bin/console types:generate

export type UUID = string;
export type Envelope<T> = { data: T };

export interface ErrorResponse {
  error: string;
  requestId: string;
  errors?: Record<string, string>;
}

export interface User {
  name: string;
  email: string;
  active: boolean;
  id: string;
  created_at?: any | null;
  updated_at?: any | null;
}

export interface GetBaseApi\s\HealthRequestQuery {
  db: string;
  request?: any;
}

export type GetBaseApi\s\HealthResponse = Envelope<any>;

export interface PostBaseApi\s\HealthRequestBody {
  db: string;
  request?: any;
}

export type PostBaseApi\s\HealthResponse = Envelope<any>;

export interface GetBaseApi\s\UserRequestQuery {
  id?: any;
  perPage?: any;
  request?: any;
}

export type GetBaseApi\s\UserResponse = Envelope<{ user: any }> | Envelope<{ users: any; perPage: number }>;

export interface GetBaseApi\s\UserRequestPath {
  id: any;
}

export interface GetBaseApi\s\UserRequestQuery {
  perPage?: any;
  request?: any;
}

export type GetBaseApi\s\UserResponse = Envelope<{ user: any }> | Envelope<{ users: any; perPage: number }>;

export interface DeleteBaseApi\s\UserRequestPath {
  id: any;
}

export interface DeleteBaseApi\s\UserRequestBody {
  perPage?: any;
  request?: any;
}

export type DeleteBaseApi\s\UserResponse = Envelope<{ message: string }>;

export interface PostBaseApi\s\LoginRequestBody {
  userId: string;
  request?: any;
}

export type PostBaseApi\s\LoginResponse = Envelope<{ userId: string }>;

export interface PostBaseApi\s\LogoutRequestBody {
  request?: any;
}

export type PostBaseApi\s\LogoutResponse = Envelope<any>;

export interface GetBaseApi\s\MeRequestQuery {
  request?: any;
}

export type GetBaseApi\s\MeResponse = Envelope<User>;
