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

export interface GetHealthRequestQuery {
  db: string;
  request?: any;
}

export type GetHealthResponse = Envelope<any>;

export interface PostHealthRequestBody {
  db: string;
  request?: any;
}

export type PostHealthResponse = Envelope<any>;

export interface GetUserRequestQuery {
  id?: any;
  perPage?: any;
  request?: any;
}

export type GetUserResponse = Envelope<{ user: any }> | Envelope<{ users: any; perPage: number }>;

export interface GetUserByIdRequestPath {
  id: any;
}

export interface GetUserByIdRequestQuery {
  perPage?: any;
  request?: any;
}

export type GetUserByIdResponse = Envelope<{ user: any }> | Envelope<{ users: any; perPage: number }>;

export interface DeleteUserByIdRequestPath {
  id: any;
}

export interface DeleteUserByIdRequestBody {
  perPage?: any;
  request?: any;
}

export type DeleteUserByIdResponse = Envelope<{ message: string }>;

export interface PostLoginRequestBody {
  userId: string;
  request?: any;
}

export type PostLoginResponse = Envelope<{ userId: string }>;

export interface PostLogoutRequestBody {
  request?: any;
}

export type PostLogoutResponse = Envelope<any>;

export interface GetMeRequestQuery {
  request?: any;
}

export type GetMeResponse = Envelope<User>;

export interface GetI18nRequestQuery {
  lang: string;
  ns: string;
  flat: boolean;
  v: string;
  request?: any;
}

export type GetI18nResponse = Envelope<any>;

export interface PostI18nRequestBody {
  lang: string;
  ns: string;
  flat: boolean;
  v: string;
  request?: any;
}

export type PostI18nResponse = Envelope<any>;
