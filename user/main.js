import App from './App'

import uvUI from '@/uni_modules/uv-ui-tools'
// 请求配置已按需导入，无需全局导入

// #ifndef VUE3
Vue.use(uvUI);
// #endif

// #ifndef VUE3
import Vue from 'vue'
import './uni.promisify.adaptor'
Vue.config.productionTip = false
App.mpType = 'app'
const app = new Vue({
  ...App
})
app.$mount()
// #endif

// #ifdef VUE3
import { createSSRApp } from 'vue'
export function createApp() {
  const app = createSSRApp(App)
  return {
    app
  }
}
// #endif