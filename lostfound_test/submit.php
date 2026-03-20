<?php include "header.php"; ?>
<?php include "sidebar.php"; ?>

<?php if (isset($_GET['success'])): ?>
    <div style="padding:15px;margin-bottom:20px;border-radius:5px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;display:flex;justify-content:space-between;align-items:center;">
        <span>Item submitted successfully! It will be reviewed by an admin before appearing in the system.</span>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:20px;padding:0;margin-left:15px;">&times;</button>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div style="padding:15px;margin-bottom:20px;border-radius:5px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;display:flex;justify-content:space-between;align-items:center;">
        <span>There was an error submitting your item. Please try again.</span>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:20px;padding:0;margin-left:15px;">&times;</button>
    </div>
<?php endif; ?>

<h2>Submit Item</h2>

<form action="save.php" method="POST" enctype="multipart/form-data">

    Type:
    <select name="type" required>
        <option value="lost">Lost</option>
        <option value="found">Found</option>
    </select>
    <br><br>

    Email:
    <input type="email" name="email" required>
    <br><br>

    Item Name:
    <input type="text" name="name" required>
    <br><br>

    Category:
    <select name="category" id="categorySelect" onchange="toggleIDFields(); updateDescriptionHint();" required>
        <option value="">-- Select Category --</option>
        <option>Electronics</option>
        <option>Wallet</option>
        <option>ID</option>
        <option>Bag</option>
        <option>Keys</option>
        <option>Clothing</option>
        <option>Books / Notes</option>
        <option>Water Bottle</option>
        <option>Umbrella</option>
        <option>Others</option>
    </select>
    <br><br>

    <div id="idFields" style="display:none; border:1px solid #ccc; padding:10px; margin:10px 0; background:#f9f9f9;">
        <h4>ID Card Details</h4>

        ID Type:
        <select name="id_type">
            <option value="">-- Select ID Type --</option>
            <option>Government ID</option>
            <option>School ID</option>
            <option>Employee ID</option>
            <option>Student ID</option>
            <option>Driver's License</option>
            <option>Passport</option>
            <option>Other</option>
        </select>
        <br><br>

        ID Number (if visible):
        <input type="text" name="id_number" placeholder="e.g. 123456789">
        <br><br>

        Issuing Authority:
        <input type="text" name="id_issuer" placeholder="e.g. Department of Education, Company Name">
        <br><br>
    </div>

    Color:
    <input type="text" name="color">
    <br><br>

    Location:
    <select name="location" id="locationSelect" onchange="toggleRoomInput()" required>
        <option value="">-- Select Location --</option>
        <option>Cafeteria</option>
        <option>Library</option>
        <option>Computer Lab</option>
        <option>Gym</option>
        <option>Admin Office</option>
        <option>Hallway</option>
        <option>Parking Area</option>
        <option value="Room">Classroom Room</option>
    </select>
    <br><br>

    <div id="roomBox" style="display:none;">
        Room Number:
        <input type="text" name="room_number" placeholder="e.g. 304">
        <br><br>
    </div>

    Description:<br>
    <textarea name="description" id="descriptionBox" placeholder="Select a category above to see helpful tips for your description."></textarea>
    <br><br>

    Image:
    <input type="file" name="image">
    <br><br>

    <button type="submit">Save</button>

</form>

<script>
    function toggleRoomInput() {
        const location = document.getElementById("locationSelect").value;
        const roomBox = document.getElementById("roomBox");

        roomBox.style.display = (location === "Room") ? "block" : "none";
    }

    function toggleIDFields() {
        const category = document.getElementById("categorySelect").value;
        const idFields = document.getElementById("idFields");

        idFields.style.display = (category === "ID") ? "block" : "none";
    }

    function updateDescriptionHint() {
        const category = document.getElementById("categorySelect").value;
        const desc = document.getElementById("descriptionBox");
        const hints = {
            "Electronics": "Describe the brand, model, color, size, and any distinguishing marks (e.g. stickers, scratches, case type).",
            "Wallet": "Describe the material, color, brand, and any visible contents (e.g. type of cards, stickers, keychains attached).",
            "ID": "Describe the ID appearance, holder/lanyard details, and any other identifying features. Fill in the ID details above if visible.",
            "Bag": "Describe the type (backpack, tote, pouch), brand, color, size, and any distinguishing features (e.g. pins, patches, keychains).",
            "Keys": "Describe the number of keys, keychain design, brand/type of keys, and any attached accessories or labels.",
            "Clothing": "Describe the type (jacket, hoodie, cap), brand, color, size, and any logos or prints.",
            "Books / Notes": "Describe the title/subject, cover color, any name written on it, number of pages, and distinguishing marks.",
            "Water Bottle": "Describe the brand, color, size, material (plastic, metal, glass), and any stickers or markings.",
            "Umbrella": "Describe the color, pattern, brand, size (compact or full), and handle type.",
            "Others": "Provide as much detail as possible: what the item is, its color, size, brand, and any unique features."
        };
        desc.placeholder = hints[category] || "Select a category above to see helpful tips for your description.";
    }
</script>

</div>
</body>

</html>