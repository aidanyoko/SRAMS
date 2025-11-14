<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Study Room Availability Monitor</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      /* Custom styles for polished look */
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
      body {
        font-family: "Inter", sans-serif;
      }

      /* Seat box sizing and transition */
      .seat-grid {
        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
      }
      .seat-box {
        height: 70px;
        transition: all 0.2s ease-in-out;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        user-select: none;
      }

      /* Status Colors */
      .status-red {
        background-color: #ef4444;
      } /* Red (Occupied) */
      .status-green {
        background-color: #10b981; /* Green (Available) */
        cursor: pointer;
      }
      .status-yellow {
        background-color: #f59e0b;
      } /* Yellow (Reserved) */

      /* Hover effect for available seats */
      .status-green:hover {
        background-color: #059669;
        transform: scale(1.05);
      }

      /* Modal Backdrop */
      .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.7);
      }

      /* Responsive adjustments for main container */
      @media (max-width: 1024px) {
        .main-container {
          flex-direction: column;
        }
        .sidebar-left,
        .sidebar-right,
        .seating-area {
          width: 100%;
          margin-bottom: 16px;
        }
      }
    </style>
  </head>
  <body
    class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300"
  >
    <!-- Modal for Reservation Confirmation -->
    <div
      id="confirmation-modal"
      class="fixed inset-0 hidden modal-backdrop items-center justify-center z-50"
    >
      <div
        class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-2xl w-full max-w-sm"
      >
        <h3 class="text-xl font-bold mb-4" id="modal-title">
          Confirm Reservation
        </h3>
        <p id="modal-message" class="mb-6">
          Do you want to reserve Seat <span id="modal-seat-id"></span> for 30
          minutes?
        </p>
        <div class="flex justify-end space-x-3">
          <button
            onclick="closeModal()"
            class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600 transition"
          >
            Cancel
          </button>
          <button
            id="confirm-reserve-btn"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold"
          >
            Confirm
          </button>
        </div>
      </div>
    </div>

    <header class="bg-indigo-700 dark:bg-indigo-900 shadow-lg py-4">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-3xl font-extrabold text-white">
          Study Room Availability Monitor 📚
        </h1>
        
        <!-- Single Auth Button -->
        @auth
          <!-- Logged in: Show Logout -->
          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button
              type="submit"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold shadow-md hover:shadow-lg flex items-center gap-2"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
              </svg>
              Logout
            </button>
          </form>
        @else
          <!-- Guest: Show Login -->
          <a
            href="{{ route('login') }}"
            class="px-4 py-2 bg-white hover:bg-gray-100 text-indigo-700 rounded-lg transition font-semibold shadow-md hover:shadow-lg flex items-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Login
          </a>
        @endauth
      </div>
    </header>

    <div
      class="main-container max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 flex flex-wrap lg:flex-nowrap gap-6"
    >
      <!-- LEFT: Room Selection & User Info -->
      <aside
        class="sidebar-left w-full lg:w-1/4 bg-white dark:bg-gray-800 p-5 rounded-xl shadow-lg h-fit"
      >
        <h2 class="text-2xl font-semibold mb-4">Select a Room</h2>
        <div id="loading-indicator" class="text-indigo-500 text-sm mb-4">
          <i class="fas fa-spinner fa-spin mr-2"></i> Initializing...
        </div>

        <label for="room-dropdown" class="block text-sm font-medium mb-2"
          >Study Room:</label
        >
        <select
          id="room-dropdown"
          class="w-full p-3 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 mb-6"
        >
          <option value="" disabled selected>Loading Rooms...</option>
        </select>

        <h3 class="text-xl font-semibold mb-2">Current User: {{ auth()->user()->email }}</h3>
        <p class="text-sm break-all">
          ID:
          <span id="user-id-display" class="font-mono text-indigo-500"
            >...</span
          >
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
          Your ID is used to track your reservations.
        </p>
      </aside>

      <!-- CENTER: Seating Chart Visualization -->
      <main
        class="seating-area w-full lg:w-2/4 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg"
      >
        <h2 class="text-3xl font-bold mb-4 text-center">
          Room:
          <span
            id="current-room-name"
            class="text-indigo-600 dark:text-indigo-400"
            >Please Select a Room</span
          >
        </h2>
        <div
          id="seat-map-visualization"
          class="seat-grid grid gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-lg min-h-[300px]"
        >
          <p
            class="placeholder-text col-span-full text-center text-gray-500 mt-12"
          >
            Select a room from the left to view the live seating map.
          </p>
        </div>
      </main>

      <!-- RIGHT: Status Key & Instructions -->
      <aside
        class="sidebar-right w-full lg:w-1/4 bg-white dark:bg-gray-800 p-5 rounded-xl shadow-lg h-fit"
      >
        <h2 class="text-2xl font-semibold mb-4">Status Key</h2>
        <ul class="space-y-3">
          <li
            class="status-key status-red p-3 rounded-lg text-white font-medium shadow-md"
          >
            Occupied (AI Detected)
          </li>
          <li
            class="status-key status-green p-3 rounded-lg text-white font-medium shadow-md"
          >
            Available (Click to Reserve)
          </li>
          <li
            class="status-key status-yellow p-3 rounded-lg text-gray-800 font-medium shadow-md"
          >
            Reserved (User Hold)
          </li>
        </ul>
        <div
          class="mt-6 p-4 bg-indigo-50 dark:bg-indigo-900 rounded-lg border-l-4 border-indigo-500"
        >
          <p class="text-sm font-medium">Your Reservation Expiration:</p>
          <p
            id="reservation-timer"
            class="text-lg font-bold text-indigo-600 dark:text-indigo-400"
          >
            N/A
          </p>
        </div>
      </aside>
    </div>

    <!-- Firebase Imports -->
    <script type="module">
      import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
      import {
        getAuth,
        signInAnonymously,
        signInWithCustomToken,
        onAuthStateChanged,
      } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
      import {
        getFirestore,
        doc,
        setDoc,
        onSnapshot,
        collection,
        query,
        getDocs,
        runTransaction,
        serverTimestamp,
      } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";

      // Ensure to initialize Firebase only once
      const firebaseConfig =
        typeof __firebase_config !== "undefined"
          ? JSON.parse(__firebase_config)
          : null;
      const appId =
        typeof __app_id !== "undefined" ? __app_id : "default-study-app";
      const initialAuthToken =
        typeof __initial_auth_token !== "undefined"
          ? __initial_auth_token
          : null;

      let app,
        db,
        auth,
        userId = null;
      let unsubscribeRoomListener = null;
      let currentRoomId = null;
      let selectedSeatId = null;
      let isAuthReady = false;

      // --- Core Functions ---

      async function initFirebase() {
        try {
          if (!firebaseConfig) {
            console.error(
              "Firebase config not found. Cannot initialize Firebase."
            );
            return;
          }
          app = initializeApp(firebaseConfig);
          db = getFirestore(app);
          auth = getAuth(app);

          // Authentication
          onAuthStateChanged(auth, async (user) => {
            if (user) {
              userId = user.uid;
            } else {
              if (initialAuthToken) {
                await signInWithCustomToken(auth, initialAuthToken);
                userId = auth.currentUser.uid;
              } else {
                const anonUser = await signInAnonymously(auth);
                userId = anonUser.user.uid;
              }
            }
            document.getElementById("user-id-display").textContent = userId;
            isAuthReady = true;
            document.getElementById("loading-indicator").textContent =
              "Loading Rooms...";
            fetchRoomList();
          });
        } catch (error) {
          console.error("Firebase initialization failed:", error);
          document.getElementById("loading-indicator").textContent =
            "Error initializing Firebase.";
        }
      }

      function getRoomCollectionRef() {
        return collection(
          db,
          "artifacts",
          appId,
          "public",
          "data",
          "study_rooms"
        );
      }

      // --- Data Population and Fetching ---

      const initialMockRooms = [
        {
          id: "R404",
          name: "Room 404 (Main Lab)",
          total_seats: 12,
          detected_occupancy: 0,
          reservations: {},
        },
        {
          id: "R201",
          name: "Room 201 (Quiet Study)",
          total_seats: 8,
          detected_occupancy: 3,
          reservations: {},
        },
        {
          id: "R310",
          name: "Room 310 (Group Pods)",
          total_seats: 10,
          detected_occupancy: 8,
          reservations: {},
        },
      ];

      async function populateInitialDataIfNeeded() {
        const roomListSnapshot = await getDocs(getRoomCollectionRef());
        if (roomListSnapshot.empty) {
          console.log("Database is empty. Populating initial data...");
          for (const room of initialMockRooms) {
            await setDoc(doc(getRoomCollectionRef(), room.id), room);
          }
        }
      }

      async function fetchRoomList() {
        await populateInitialDataIfNeeded();

        const dropdown = document.getElementById("room-dropdown");
        dropdown.innerHTML =
          '<option value="" disabled selected>Choose a Room...</option>';

        const roomListSnapshot = await getDocs(getRoomCollectionRef());
        const rooms = [];
        roomListSnapshot.forEach((doc) => {
          const room = { id: doc.id, ...doc.data() };
          rooms.push(room);
          const option = document.createElement("option");
          option.value = room.id;
          option.textContent = room.name;
          dropdown.appendChild(option);
        });

        if (rooms.length > 0) {
          dropdown.value = rooms[0].id;
          handleRoomSelection(rooms[0].id);
        }
        document.getElementById("loading-indicator").style.display = "none";
      }

      // --- Event Handlers ---

      document
        .getElementById("room-dropdown")
        .addEventListener("change", (event) => {
          handleRoomSelection(event.target.value);
        });

      document
        .getElementById("confirm-reserve-btn")
        .addEventListener("click", () => {
          closeModal();
          reserveSeat(selectedSeatId);
        });

      function handleRoomSelection(roomId) {
        if (unsubscribeRoomListener) {
          unsubscribeRoomListener();
        }
        currentRoomId = roomId;
        listenToRoomData(roomId);
      }

      function listenToRoomData(roomId) {
        const roomDocRef = doc(getRoomCollectionRef(), roomId);

        unsubscribeRoomListener = onSnapshot(
          roomDocRef,
          (docSnapshot) => {
            if (docSnapshot.exists()) {
              const roomData = { id: docSnapshot.id, ...docSnapshot.data() };
              renderSeatMap(roomData);
              checkUserReservation(roomData);
            } else {
              document.getElementById("current-room-name").textContent =
                "Room Not Found";
              document.getElementById("seat-map-visualization").innerHTML =
                '<p class="text-center text-red-500 mt-12">Room data could not be loaded.</p>';
            }
          },
          (error) => {
            console.error("Error listening to room data:", error);
          }
        );
      }

      // --- Rendering Logic ---

      function renderSeatMap(room) {
        const mapContainer = document.getElementById("seat-map-visualization");
        mapContainer.innerHTML = "";
        document.getElementById("current-room-name").textContent = room.name;

        const occupiedCount = room.detected_occupancy || 0;
        const totalSeats = room.total_seats || 0;
        const reservations = room.reservations || {};
        const currentTime = Date.now();

        const actualOccupied = Math.min(occupiedCount, totalSeats);

        for (let i = 1; i <= totalSeats; i++) {
          const seatBox = document.createElement("div");
          const seatId = `seat-${i}`;
          seatBox.classList.add(
            "seat-box",
            "rounded-lg",
            "shadow-md",
            "p-2",
            "font-bold",
            "text-sm"
          );
          seatBox.dataset.seatId = i;
          seatBox.innerHTML = `S${i}<span class="text-xs font-normal opacity-75">${room.id}</span>`;

          let status = "available";

          const reservation = reservations[seatId];
          if (reservation) {
            if (reservation.expires < currentTime) {
              status = "expired";
              clearExpiredReservation(room.id, seatId);
            } else {
              status = "reserved";
              seatBox.innerHTML = `S${i}<span class="text-xs font-normal block">Reserved</span>`;
            }
          }

          if (status !== "reserved" && i <= actualOccupied) {
            status = "occupied";
            seatBox.innerHTML = `S${i}<span class="text-xs font-normal block">Occupied</span>`;
          }

          if (status === "occupied") {
            seatBox.classList.add("status-red", "text-white");
          } else if (status === "reserved") {
            seatBox.classList.add("status-yellow", "text-gray-900");
            if (reservation.userId === userId) {
              seatBox.classList.add(
                "border-4",
                "border-indigo-600",
                "dark:border-indigo-400"
              );
              seatBox.innerHTML = `S${i}<span class="text-xs font-normal block">Your Hold</span>`;
            }
          } else {
            seatBox.classList.add("status-green", "text-white");
            seatBox.addEventListener("click", () => showConfirmationModal(i));
          }

          mapContainer.appendChild(seatBox);
        }
      }

      // --- Reservation & Cleanup ---

      async function clearExpiredReservation(roomId, seatId) {
        const roomDocRef = doc(getRoomCollectionRef(), roomId);

        try {
          await runTransaction(db, async (transaction) => {
            const roomDoc = await transaction.get(roomDocRef);
            if (roomDoc.exists()) {
              const reservations = roomDoc.data().reservations || {};

              if (
                reservations[seatId] &&
                reservations[seatId].expires < Date.now()
              ) {
                delete reservations[seatId];
                transaction.update(roomDocRef, { reservations: reservations });
                console.log(
                  `Cleaned up expired reservation for ${seatId} in ${roomId}.`
                );
              }
            }
          });
        } catch (e) {
          console.error("Transaction failed to clear expired reservation:", e);
        }
      }

      async function reserveSeat(seatNumber) {
        const roomId = currentRoomId;
        const seatId = `seat-${seatNumber}`;
        const roomDocRef = doc(getRoomCollectionRef(), roomId);
        const expirationTime = Date.now() + 30 * 60 * 1000;

        try {
          await runTransaction(db, async (transaction) => {
            const roomDoc = await transaction.get(roomDocRef);
            if (!roomDoc.exists()) {
              throw new Error("Room does not exist!");
            }

            const reservations = roomDoc.data().reservations || {};
            const currentReservation = reservations[seatId];

            if (currentReservation && currentReservation.expires > Date.now()) {
              throw new Error("Seat was just reserved by another user!");
            }

            const newReservation = {
              userId: userId,
              reservedAt: serverTimestamp(),
              expires: expirationTime,
            };

            reservations[seatId] = newReservation;

            transaction.update(roomDocRef, { reservations: reservations });

            showStatusMessage(
              `Successfully reserved Seat S${seatNumber} for 30 minutes!`,
              "success"
            );
          });
        } catch (e) {
          console.error("Reservation failed:", e);
          showStatusMessage(
            e.message.includes("reserved")
              ? "Seat was taken! Please try another."
              : "Reservation failed due to an error.",
            "error"
          );
        }
      }

      // --- Status & Timer Functions ---

      function showStatusMessage(message, type) {
        console.log(`[Status ${type.toUpperCase()}]: ${message}`);
        const header = document.querySelector("header");
        const statusDiv = document.createElement("div");
        statusDiv.textContent = message;
        statusDiv.className = `p-3 text-center text-white font-semibold ${
          type === "success" ? "bg-green-600" : "bg-red-600"
        } transition-all duration-300`;
        header.appendChild(statusDiv);
        setTimeout(() => statusDiv.remove(), 3000);
      }

      function checkUserReservation(room) {
        const reservations = room.reservations || {};
        const userRes = Object.keys(reservations).find(
          (key) =>
            reservations[key].userId === userId &&
            reservations[key].expires > Date.now()
        );
        const timerEl = document.getElementById("reservation-timer");

        clearInterval(timerEl.timerInterval);

        if (userRes) {
          const expires = reservations[userRes].expires;
          const seatNumber = userRes.split("-")[1];

          const updateTimer = () => {
            const remaining = expires - Date.now();
            if (remaining > 0) {
              const minutes = Math.floor(remaining / 60000);
              const seconds = Math.floor((remaining % 60000) / 1000);
              timerEl.innerHTML = `S${seatNumber} expires in ${minutes}m ${seconds}s`;
            } else {
              timerEl.innerHTML = `Your hold on S${seatNumber} has expired.`;
              clearInterval(timerEl.timerInterval);
            }
          };

          updateTimer();
          timerEl.timerInterval = setInterval(updateTimer, 1000);
        } else {
          timerEl.innerHTML = "N/A";
        }
      }

      // --- Modal Functions ---

      function showConfirmationModal(seatNumber) {
        selectedSeatId = seatNumber;
        document.getElementById("modal-seat-id").textContent = `S${seatNumber}`;
        document
          .getElementById("confirmation-modal")
          .classList.remove("hidden");
        document.getElementById("confirmation-modal").classList.add("flex");
      }

      function closeModal() {
        document.getElementById("confirmation-modal").classList.add("hidden");
        document.getElementById("confirmation-modal").classList.remove("flex");
        selectedSeatId = null;
      }

      // --- Initialization ---
      initFirebase();
    </script>
  </body>
</html>