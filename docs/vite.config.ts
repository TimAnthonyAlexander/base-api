import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
    plugins: [react()],
    base: '/', // Set for GitHub Pages deployment
    server: {
        port: 7874,
        open: true,
    },
})
