const { defineConfig } = require('@vue/cli-service')

module.exports = defineConfig({
  transpileDependencies: true,
  lintOnSave: false,
  configureWebpack: {
    externals: {
      axios: 'axios',
      'vue-router': 'VueRouter',
    }
  }
})
