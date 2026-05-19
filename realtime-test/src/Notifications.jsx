import { useEffect, useState } from "react";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import echo from "./echo";

export default function Notifications() {
  const [notifications, setNotifications] = useState([]);

  useEffect(() => {
    const userId = 25;

    // const token = "153|TtaF6Rx9oO17aw2SAsjR0v3u8pQEVcM9CMNTNMlu579ccf72";

    // مهم جدًا
    window.Pusher = Pusher;
    Pusher.logToConsole = true;

    // 🔥 القناة الصح
    const channel = echo.private(`notifications.${userId}`);

    channel.listen(".NewNotificationEvent", (e) => {
      console.log("🔥 New Notification:", e);

      setNotifications((prev) => [e, ...prev]);
    });

    return () => {
      echo.disconnect();
    };
  }, []);

  return (
    <div style={{ padding: "20px" }}>
      <h2>🔔 Notifications</h2>

      {notifications.length === 0 && <p>No notifications yet</p>}

      {notifications.map((n, i) => (
        <div
          key={i}
          style={{
            margin: "10px 0",
            padding: "10px",
            background: "#e3f2fd",
            borderRadius: "8px",
          }}
        >
          <b>{n.title}</b>
          <p>{n.message}</p>
          <small>{n.created_at}</small>
        </div>
      ))}
    </div>
  );
}