import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Store the Echo instance outside the plugin so it persists
let echoInstance: Echo | null = null

function createEcho(token: string): Echo {
  const echo = new Echo({
    broadcaster:       'reverb',
    key:               'xzfyvnawjkjkgyians2y',
    wsHost:            'localhost',
    wsPort:            8081,
    forceTLS:          false,
    disableStats:      true,
    enabledTransports: ['ws'],

    // Custom authorizer reads the token fresh on every auth request
    authorizer: (channel: any) => ({
      authorize: (socketId: string, callback: Function) => {
        const authStore = useAuthStore()
        const currentToken = authStore.accessToken

        if (!currentToken) {
          callback(new Error('No access token available'), null)
          return
        }

        fetch('http://localhost:8000/api/broadcasting/auth', {
          method:      'POST',
          credentials: 'include',
          headers: {
            'Content-Type':  'application/json',
            'Accept':        'application/json',
            'Authorization': `Bearer ${currentToken}`,
          },
          body: JSON.stringify({
            socket_id:    socketId,
            channel_name: channel.name,
          }),
        })
          .then(r => {
            if (!r.ok) throw new Error(`Auth failed: ${r.status}`)
            return r.json()
          })
          .then(data => callback(null, data))
          .catch(err  => callback(err, null))
      },
    }),
  })

  return echo
}

export default defineNuxtPlugin((nuxtApp) => {
  window.Pusher = Pusher

  // Return a getter function instead of the instance directly.
  // This way components can get the current Echo instance
  // even if it was created after the plugin initialized.
  const getEcho = (): Echo | null => echoInstance

  const initEcho = (token: string): Echo => {
    // Disconnect existing instance before creating a new one
    if (echoInstance) {
      try {
        echoInstance.disconnect()
      } catch {}
    }
    echoInstance = createEcho(token)
    return echoInstance
  }

  const disconnectEcho = (): void => {
    if (echoInstance) {
      try {
        echoInstance.disconnect()
      } catch {}
      echoInstance = null
    }
  }

  return {
    provide: {
      echo:           getEcho,
      initEcho,
      disconnectEcho,
    },
  }
})