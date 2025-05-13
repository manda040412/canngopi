/* access */
let userIDToDelete;      
    
        function showPopup(userID) {
            userIDToDelete = userID;
            document.getElementById("popup").classList.add("show");
        }

        function closePopup() {
            document.getElementById("popup").classList.remove("show");
        }

        function confirmDeletion() {
            window.location.href = 'access.php?deleteUserID=' + encodeURIComponent(userIDToDelete);
        }

      function closePopup2() {
        document.getElementById("popup2").classList.remove("show");
      }
      function showPopup3() {
        document.getElementById("popup3").classList.add("show");
      }

      function closePopup3() {
        window.location.href = "access.php";
      }

      function closePopup() {
          document.getElementById("popup").classList.remove("show");
      }

      function confirmDeletion() {
          window.location.href = 'access.php?deleteUserID=' + encodeURIComponent(userIDToDelete);
      }