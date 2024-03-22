import axios, { Method } from "axios"

export interface ActionRoute {
  uri: string
  methods: Method[]
  as: string
}

export default class {

  public route: ActionRoute|undefined

  public params: {[key: string]: any}

  constructor(params: {[key: string]: any}) {
    this.params = params
  }

  run(params: {[key: string]: any} = {}) {
    if (!this.route) {
      throw new Error('Route data not set')
    }

    const response = axios.create({
      baseURL: '/'
    }).request({
      url: this.route.uri,
      method: this.route.methods[0],
      data: params
    }).then(response => response.data).catch(error => error).then(response => response)

    return response
  }
}
