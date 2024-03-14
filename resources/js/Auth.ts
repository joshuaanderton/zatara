import { AxiosResponse } from "axios"
import Connection from "./Connection"

interface Model {[name: string]: any}

export default class extends Connection {

  async user(): Promise<Model|null> {
    return (await this.get('auth.user') as any).user || null
  }

  async check(): Promise<boolean> {
    return !!(await this.get('auth.user') as any).check
  }

  constructor() {
    super()
  }
}
