import { default as ziggyRoute } from "ziggy-js"

export default class {

  route: string

  params: {[key: string]: any} = {}

  constructor(route: string, params?: {[key: string]: any}) {
    this.route = route
    this.params = params || {}
  }

  set(params: {[key: string]: any}) {
    this.params = {...this.params, ...params}
  }

  submit() {
    const url = ziggyRoute(this.route, this.params)

    // Get route method from ziggy
    // if (method === 'GET') {
    //   return axios.get(url)
    // }
  }
}
