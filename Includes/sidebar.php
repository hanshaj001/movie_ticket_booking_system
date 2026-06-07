<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title> </title>
  <link rel="stylesheet" href="../Assets/sidebar.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
  
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
  <div class="logo">
    <h2>MovieAdmin</h2>
  </div>

  <ul>
    <li class="active"><i class="fa fa-home"></i> Dashboard</li>
    <li><i class="fa fa-film"></i> Manage Movies</li>
    <li><i class="fa fa-plus"></i> Add Show</li>
    <li><i class="fa fa-chair"></i> Seat Monitoring</li>
    <li><i class="fa fa-ticket"></i> Booking Monitoring</li>
    <li class="logout"><i class="fa fa-sign-out-alt"></i> Logout</li>
  </ul>
</div>

<!-- MAIN AREA -->
<div class="main">

  <!-- NAVBAR -->
  <div class="navbar">
    
    <div class="left">
      <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fa fa-bars"></i>
      </button>
      <h3>Admin Panel</h3>
    </div>

    <div class="right">
      <span id="datetime"></span>
      <div class="admin">
        <i class="fa fa-user-circle"></i>
        Admin
      </div>
    </div>
  </div>

</div>

<script>
  // Sidebar toggle (mobile)
  function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
  }

  // Live date & time
  function updateTime(){
    const now = new Date();
    document.getElementById("datetime").innerText =
      now.toLocaleDateString() + " | " + now.toLocaleTimeString();
  }
  setInterval(updateTime, 1000);
  updateTime();

  // Active menu highlight
  const items = document.querySelectorAll(".sidebar ul li");
  items.forEach(item=>{
    item.addEventListener("click", ()=>{
      items.forEach(i=>i.classList.remove("active"));
      item.classList.add("active");
    });
  });
</script>

</body>
</html>