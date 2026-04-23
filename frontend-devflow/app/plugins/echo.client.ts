import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

export default defineNuxtPlugin(() => {
  window.Pusher = Pusher

  const authStore = useAuthStore()

  // Use a custom authorizer so the token is read fresh
  // at the exact moment each channel auth request fires —
  // not once at plugin init time.
  const echo = new Echo({
    broadcaster:       'reverb',
    key:               'xzfyvnawjkjkgyians2y',
    wsHost:            'localhost',
    wsPort:            8081,
    forceTLS:          false,
    disableStats:      true,
    enabledTransports: ['ws'],

    // Custom authorizer: called fresh for every channel subscription.
    // This guarantees the current token is used even after a silent
    // refresh rotated the access token between page load and subscribe.
    authorizer: (channel: any) => {
      return {
        authorize: (socketId: string, callback: Function) => {
          const token = authStore.accessToken

          if (!token) {
            callback(new Error('No access token'), null)
            return
          }

          fetch('http://localhost:8000/api/broadcasting/auth', {
            method:      'POST',
            credentials: 'include',
            headers: {
              'Content-Type':  'application/json',
              'Accept':        'application/json',
              'Authorization': `Bearer ${token}`,
            },
            body: JSON.stringify({
              socket_id:    socketId,
              channel_name: channel.name,
            }),
          })
            .then(response => {
              if (!response.ok) {
                throw new Error(`Auth failed: ${response.status}`)
              }
              return response.json()
            })
            .then(data => callback(null, data))
            .catch(err => callback(err, null))
        },
      }
    },
  })

  return {
    provide: { echo },
  }
})