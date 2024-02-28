import { UserConfig } from 'vite'
import path from 'path'

/**
 * Export vite plugin as default
 */
export default () => ({

  name: 'zatara',

  config: (config: UserConfig): UserConfig => {

    config.resolve = config.resolve || {}

    config.resolve.alias = {
      ...(config.resolve.alias || {}),
      '@zatara': path.resolve(`${__dirname}/resources/js`),
    }

    return config
  }
})
