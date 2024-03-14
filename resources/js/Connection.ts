import axios, { Axios, AxiosResponse } from "axios"

export default class {

  route: string = '/zatara'

  constructor() {
    //
  }

  async post(action: string, params?: {}): Promise<AxiosResponse> {
    return await axios.post(`${this.route}/${action}`, { params }).then(response => response.data)
  }

  async get(action: string, params?: {}): Promise<AxiosResponse> {
    return await axios.get(`${this.route}/${action}`, { params }).then(response => response.data)
  }
}
