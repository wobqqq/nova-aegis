import '../css/tool.css'
import Tool from './pages/Tool.vue'
import AegisCard from './components/AegisCard.vue'

Nova.inertia('Aegis', Tool)

Nova.booting((app) => {
    app.component('AegisCard', AegisCard)
})
