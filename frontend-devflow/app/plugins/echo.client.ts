import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

export default defineNuxtPlugin(() => {
  window.Pusher = Pusher

  const authStore = useAuthStore()

  const echo = new Echo({
    broadcaster:       'reverb',
    key:               'xzfyvnawjkjkgyians2y',
    wsHost:            'localhost',
    wsPort:            8081,
    forceTLS:          false,
    disableStats:      true,
    enabledTransports: ['ws'],

    authorizer: (channel: any) => ({
      authorize: (socketId: string, callback: Function) => {
        const token = authStore.accessToken
        if (!token) {
          callback(new Error('No token'), null)
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
          .then(r => {
            if (!r.ok) throw new Error(`Auth ${r.status}`)
            return r.json()
          })
          .then(data => callback(null, data))
          .catch(err => callback(err, null))
      },
    }),
  })

  return { provide: { echo } }
})