<?php 
include "config.php"; 
include "header.php"; 
include "sidebar.php"; 
?>

<h2>Matched Items</h2>

<?php

$query = $conn->query("
    SELECT m.*, 
           l.name AS lost_name,
           l.description AS lost_description,
           l.image AS lost_image,
           l.category AS lost_category,
           l.location AS lost_location,

           f.name AS found_name,
           f.description AS found_description,
           f.image AS found_image,
           f.category AS found_category,
           f.location AS found_location

    FROM matches m
    JOIN items l ON m.lost_item_id = l.id
    JOIN items f ON m.found_item_id = f.id
    ORDER BY m.date_matched DESC
");

if($query->num_rows == 0){
    echo "<p>No matched items yet.</p>";
}

while($row = $query->fetch_assoc()){

    echo "<div style='margin-bottom:30px;padding:20px;border:1px solid #ccc;border-radius:10px;background:#f9f9f9;'>";

    echo "<h3 style='margin-top:0;'>
            ".htmlspecialchars($row['lost_name'])." 
            ↔ 
            ".htmlspecialchars($row['found_name'])."
          </h3>";

    echo "<b>Match Score:</b> ".$row['match_score']."%<br>";
    echo "<b>Matched On:</b> ".$row['date_matched']."<br><br>";

    echo "<div style='display:flex;gap:40px;'>";

    // LOST ITEM
    echo "<div style='width:45%;'>";
    echo "<h4 style='color:red;'>Lost Item</h4>";
    echo "<b>Category:</b> ".htmlspecialchars($row['lost_category'])."<br>";
    echo "<b>Location:</b> ".htmlspecialchars($row['lost_location'])."<br>";
    echo "<b>Description:</b><br>";
    echo "<div style='margin-bottom:10px;'>".nl2br(htmlspecialchars($row['lost_description']))."</div>";

    if(!empty($row['lost_image'])){
        echo "<img src='uploads/".htmlspecialchars($row['lost_image'])."' 
              width='200' 
              style='border-radius:8px;border:1px solid #ddd;'>";
    }

    echo "</div>";


    // FOUND ITEM
    echo "<div style='width:45%;'>";
    echo "<h4 style='color:green;'>Found Item</h4>";
    echo "<b>Category:</b> ".htmlspecialchars($row['found_category'])."<br>";
    echo "<b>Location:</b> ".htmlspecialchars($row['found_location'])."<br>";
    echo "<b>Description:</b><br>";
    echo "<div style='margin-bottom:10px;'>".nl2br(htmlspecialchars($row['found_description']))."</div>";

    if(!empty($row['found_image'])){
        echo "<img src='uploads/".htmlspecialchars($row['found_image'])."' 
              width='200' 
              style='border-radius:8px;border:1px solid #ddd;'>";
    }

    echo "</div>";

    echo "</div>"; // flex container
    echo "</div>"; // card
}

?>

</div>
</body>
</html>