import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: "pusher",
  key: "3bd13b9e261d210328ff",
  cluster: "eu",
  forceTLS: true,

  authEndpoint: "http://127.0.0.1:8000/api/broadcasting/auth",

  auth: {
    // headers: {
    //   Accept: "application/json",
    //   Authorization: `Bearer 153|TtaF6Rx9oO17aw2SAsjR0v3u8pQEVcM9CMNTNMlu579ccf72`,
    // },
  withCredentials: true
  },
});

export default echo;