
// server.js
const express = require("express");
const bodyParser = require("body-parser");
const cors = require("cors");
const mysql = require("mysql2");

const app = express();
app.use(cors());
app.use(bodyParser.json());

// 🔗 Connect to your MySQL database
const db = mysql.createConnection({
  host: "localhost",
  user: "root",
  password: "", // if you set a password in XAMPP, add it here
  database: "payment", // your database name
});

db.connect((err) => {
  if (err) {
    console.error("❌ Database connection failed:", err);
  } else {
    console.log("✅ Connected to MySQL Database");
  }
});

// 🛠 API endpoint to update order status
app.post("/update-status", (req, res) => {
  const { id, status } = req.body;

  if (!id || !status) {
    return res.json({ success: false, message: "Invalid input" });
  }

  const query = "UPDATE payment SET status = ? WHERE id = ?";
  db.query(query, [status, id], (err, result) => {
    if (err) {
      console.error("❌ Error updating status:", err);
      return res.json({ success: false, message: "Database error" });
    }

    res.json({ success: true, message: "Status updated successfully!" });
  });
});

// 🟢 Start the server
app.listen(3000, () => {
  console.log("🚀 Node.js server running at http://localhost:3000");
});

$(document).ready(function(){
    function fetchNotifCount(){
        $.get('/systemcutie/components/fetch_notif_count.php', function(data){
            $('#notifCount').text(data > 0 ? `(${data})` : '');
        });
    }
    fetchNotifCount(); // initial
    setInterval(fetchNotifCount, 5000); // refresh every 5s
});
