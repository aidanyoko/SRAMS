<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Study Room Availability Monitor</title>

    <!-- Tailwind -->
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
          Do you want to reserve this seat?
        </p>

        <!-- These hidden spans are needed by the JavaScript -->
        <span id="modal-seat-id" class="hidden"></span>
        <span id="modal-room-id" class="hidden"></span>

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

    <!-- Header -->
    <header class="bg-indigo-700 dark:bg-indigo-900 shadow-lg py-4">
      <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center"
      >
        <h1 class="text-3xl font-extrabold text-white">
          Study Room Availability Monitor 📚
        </h1>

        <!-- Auth Button -->
        @auth
          <!-- Logged in: Show Logout -->
          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button
              type="submit"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold shadow-md hover:shadow-lg flex items-center gap-2"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z"
                  clip-rule="evenodd"
                />
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
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z"
                clip-rule="evenodd"
              />
            </svg>
            Login
          </a>
        @endauth
      </div>
    </header>

    <!-- Main Layout -->
    <div
      class="main-container max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 flex flex-wrap lg:flex-nowrap gap-6"
    >
      <!-- LEFT: Room Selection & User Info -->
      <aside
        class="sidebar-left w-full lg:w-1/4 bg-white dark:bg-gray-800 p-5 rounded-xl shadow-lg h-fit"
      >
        <h2 class="text-2xl font-semibold mb-4">Select a Room</h2>

        <div id="loading-indicator" class="text-indigo-500 text-sm mb-4">
          <i class="fas fa-spinner fa-spin mr-2"></i>
          Initializing...
        </div>

        <!-- AI Detection Status -->
        <div
          id="ai-status"
          class="mb-4 p-3 bg-blue-50 dark:bg-blue-900 rounded-lg border-l-4 border-blue-500 hidden"
        >
          <p class="text-xs font-semibold text-blue-700 dark:text-blue-300">
            🤖 AI Detection:
            <span id="ai-status-text">Connecting...</span>
          </p>
          <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
            Rooms loaded from Python API
          </p>
        </div>

        <!-- Python API Link -->
        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-xs">
          <p class="font-semibold mb-1">Manage Rooms:</p>
          <a
            href="http://127.0.0.1:8000/classroom"
            target="_blank"
            class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
          >
            <span>→</span>
            Add/Edit rooms in Python system
          </a>
        </div>

        <label for="room-dropdown" class="block text-sm font-medium mb-2">
          Study Room:
        </label>

        <select
          id="room-dropdown"
          class="w-full p-3 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 mb-6"
        >
          <option value="" disabled selected>Loading Rooms...</option>
        </select>

        <h3 class="text-xl font-semibold mb-2">
          Current User:
          @auth
            <span class="text-indigo-500">{{ Auth::user()->email }}</span>
          @else
            <span class="text-gray-500">Guest</span>
          @endauth
        </h3>

        <p class="text-sm text-gray-500">
          User ID:
          <span id="user-id-display"></span>
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
          >
            Please Select a Room
          </span>
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

    <!-- Firebase + App Script -->
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

      // ---------------------------------------------------------------------
      // Firebase / Global State
      // ---------------------------------------------------------------------

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

      let app;
      let db;
      let auth;
      let userId = null;
      let unsubscribeRoomListener = null;
      let currentRoomId = null;
      let selectedSeatId = null;
      let isAuthReady = false;
      const isGuestUser = "{{ Auth::check() ? 'false' : 'true' }}" === "true";

      // Python AI Detection API
      const PYTHON_API_URL = "http://127.0.0.1:8000";
      const POLL_INTERVAL = 3000;

      let pollingInterval = null;

      // ---------------------------------------------------------------------
      // Firebase Init
      // ---------------------------------------------------------------------

      async function initFirebase() {
        try {
          // If no firebase config → local mode
          if (!firebaseConfig) {
            console.warn("Firebase not configured. Running in local mode.");
            document.getElementById("loading-indicator").textContent =
              "Loading Rooms (Local Mode)...";

            // Local anonymous user
            userId = "local-user-" + Math.random().toString(36).substr(2, 9);
            document.getElementById("user-id-display").textContent = userId;
            isAuthReady = true;

            await loadRoomsLocally();
            startAICountPolling();
            return;
          }

          // Firebase app
          app = initializeApp(firebaseConfig);
          db = getFirestore(app);
          auth = getAuth(app);
          // Detect if user is a guest (Laravel adds this automatically)
          

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

            await fetchRoomList();
            startAICountPolling();
          });
        } catch (error) {
          console.error("Firebase initialization failed:", error);
          document.getElementById("loading-indicator").textContent =
            "Error initializing. Running in local mode...";

          // Fallback to local mode
          userId = "local-user-" + Math.random().toString(36).substr(2, 9);
          document.getElementById("user-id-display").textContent = userId;
          isAuthReady = true;

          await loadRoomsLocally();
          startAICountPolling();
        }
      }

      // ---------------------------------------------------------------------
      // Local Mode / Python API
      // ---------------------------------------------------------------------

      let localRooms = {};
      let localCurrentRoom = null;

      async function loadRoomsLocally() {
        const dropdown = document.getElementById("room-dropdown");
        dropdown.innerHTML =
          '<option value="" disabled selected>Loading rooms from AI system...</option>';

        try {
          const response = await fetch(`${PYTHON_API_URL}/counts`);
          if (!response.ok) {
            throw new Error("Python API not responding");
          }

          const counts = await response.json();
          console.log("Fetched rooms from Python API:", counts);

          const roomKeys = Object.keys(counts);

          if (roomKeys.length === 0) {
            dropdown.innerHTML =
              '<option value="" disabled selected>No rooms found. Create rooms in Python first.</option>';

            document.getElementById("loading-indicator").innerHTML =
              '<a href="http://127.0.0.1:8000/classroom" target="_blank" class="text-indigo-600 hover:underline">Create rooms in Python API →</a>';

            return;
          }

          dropdown.innerHTML =
            '<option value="" disabled selected>Choose a Room...</option>';

          roomKeys.forEach((roomId) => {
            const currentOccupancy = counts[roomId];

            const room = {
              id: roomId,
              name: getRoomName(roomId),
              description: getRoomDescription(roomId),
              total_seats: 12,
              detected_occupancy: currentOccupancy,
              capacity: 12,
              has_projector: getRoomFeature(roomId, "projector"),
              has_whiteboard: getRoomFeature(roomId, "whiteboard"),
              has_computers: getRoomFeature(roomId, "computer"),
              reservations: {},
              is_available: currentOccupancy === 0,
            };

            localRooms[roomId] = room;

            const option = document.createElement("option");
            option.value = roomId;

            const status =
              currentOccupancy === 0
                ? "🟢 Available"
                : `🔴 ${currentOccupancy} people inside`;

            option.textContent = `${room.name} - ${status}`;
            dropdown.appendChild(option);
          });

          if (roomKeys.length > 0) {
            dropdown.value = roomKeys[0];
            handleRoomSelectionLocal(roomKeys[0]);
          }

          document.getElementById("loading-indicator").style.display = "none";
        } catch (error) {
          console.error("Failed to load rooms from Python API:", error);

          dropdown.innerHTML =
            '<option value="" disabled selected>Error: Python API not running</option>';

          document.getElementById("loading-indicator").innerHTML =
            '<div class="text-red-600">' +
            "Python API not found. <br>" +
            '<a href="http://127.0.0.1:8000/classroom" target="_blank" class="text-indigo-600 hover:underline">Start Python server and create rooms →</a>' +
            "</div>";
        }
      }

      function getRoomName(roomId) {
        const names = {
          R101: "Room 101 - Quiet Study",
          R201: "Room 201 - Collaboration Hub",
          R301: "Room 301 - Tech Lab",
          R102: "Room 102 - Small Meeting",
          R404: "Room 404 - Presentation Room",
          R202: "Room 202 - Discussion Pod",
        };
        return names[roomId] || `Room ${roomId}`;
      }

      function getRoomDescription(roomId) {
        const descriptions = {
          R101: "Perfect for individual study and focused work",
          R201: "Ideal for group projects and team collaboration",
          R301: "Equipped with computers and tech equipment",
          R102: "Cozy space for 1-3 people",
          R404: "Large room with presentation equipment",
          R202: "Circular seating for engaging discussions",
        };
        return (
          descriptions[roomId] ||
          `Study room ${roomId} with AI occupancy detection`
        );
      }

      function getRoomFeature(roomId, feature) {
        const features = {
          R101: { projector: false, whiteboard: true, computer: false },
          R201: { projector: true, whiteboard: true, computer: false },
          R301: { projector: true, whiteboard: true, computer: true },
          R102: { projector: false, whiteboard: true, computer: false },
          R404: { projector: true, whiteboard: true, computer: true },
          R202: { projector: false, whiteboard: true, computer: false },
        };
        return features[roomId]?.[feature] || false;
      }

      function handleRoomSelectionLocal(roomId) {
        localCurrentRoom = roomId;
        const room = localRooms[roomId];
        if (room) {
          renderSeatMap(room);
          checkUserReservationLocal(room);
        }
      }

      function startAICountPolling() {
        if (pollingInterval) {
          clearInterval(pollingInterval);
        }

        updateAIDetectionCounts();
        pollingInterval = setInterval(updateAIDetectionCounts, POLL_INTERVAL);
      }

      async function updateAIDetectionCounts() {
        try {
          const response = await fetch(`${PYTHON_API_URL}/counts`);
          if (!response.ok) {
            console.warn("Python API not responding");
            updateAIStatus("Disconnected", false);
            return;
          }

          const counts = await response.json();
          console.log("AI Detection Counts:", counts);

          if (!firebaseConfig) {
            localRooms = Object.keys(counts).reduce((acc, roomId) => {
              const currentOccupancy = counts[roomId];
              acc[roomId] = {
                ...(localRooms[roomId] || {}),
                id: roomId,
                name: getRoomName(roomId),
                description: getRoomDescription(roomId),
                detected_occupancy: currentOccupancy,
                capacity: localRooms[roomId]?.capacity || 12,
                reservations: localRooms[roomId]?.reservations || {},
              };
              return acc;
            }, {});

            if (localCurrentRoom && localRooms[localCurrentRoom]) {
              renderSeatMap(localRooms[localCurrentRoom]);
              checkUserReservationLocal(localRooms[localCurrentRoom]);
            }
          }

          updateAIStatus("Connected", true);
        } catch (error) {
          console.error("Error fetching AI counts:", error);
          updateAIStatus("Disconnected", false);
        }
      }

      function updateAIStatus(status, connected) {
        const aiStatus = document.getElementById("ai-status");
        const aiStatusText = document.getElementById("ai-status-text");

        aiStatus.classList.remove("hidden");
        aiStatusText.textContent = status;

        if (connected) {
          aiStatus.classList.add("bg-blue-50", "dark:bg-blue-900");
          aiStatus.classList.remove("bg-red-50", "dark:bg-red-900");
        } else {
          aiStatus.classList.add("bg-red-50", "dark:bg-red-900");
          aiStatus.classList.remove("bg-blue-50", "dark:bg-blue-900");
        }
      }

      // ---------------------------------------------------------------------
      // Firestore Helpers (Firebase mode)
      // ---------------------------------------------------------------------

      function getRoomCollectionRef() {
        return collection(db, `institutions/${appId}/rooms`);
      }

      async function populateInitialDataIfNeeded() {
        const roomListSnapshot = await getDocs(getRoomCollectionRef());
        if (!roomListSnapshot.empty) return;

        const defaultRooms = [
          {
            id: "room-a",
            name: "Room A - Quiet Study",
            description: "Silent room with 12 seats",
            total_seats: 12,
            detected_occupancy: 0,
            capacity: 12,
            has_projector: false,
            has_whiteboard: true,
            has_computers: false,
            reservations: {},
          },
          {
            id: "room-b",
            name: "Room B - Collaboration",
            description: "Group work friendly room",
            total_seats: 12,
            detected_occupancy: 0,
            capacity: 12,
            has_projector: true,
            has_whiteboard: true,
            has_computers: false,
            reservations: {},
          },
          {
            id: "room-c",
            name: "Room C - Tech Lab",
            description: "Computers and projector included",
            total_seats: 12,
            detected_occupancy: 0,
            capacity: 12,
            has_projector: true,
            has_whiteboard: true,
            has_computers: true,
            reservations: {},
          },
        ];

        await Promise.all(
          defaultRooms.map((room) =>
            setDoc(doc(getRoomCollectionRef(), room.id), room)
          )
        );
      }

      async function fetchRoomList() {
        await populateInitialDataIfNeeded();

        const dropdown = document.getElementById("room-dropdown");
        dropdown.innerHTML =
          '<option value="" disabled selected>Choose a Room...</option>';

        const roomListSnapshot = await getDocs(getRoomCollectionRef());
        const rooms = [];

        roomListSnapshot.forEach((docSnap) => {
          const room = { id: docSnap.id, ...docSnap.data() };
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

      // ---------------------------------------------------------------------
      // Event Handlers
      // ---------------------------------------------------------------------

      document
        .getElementById("room-dropdown")
        .addEventListener("change", (event) => {
          const roomId = event.target.value;
          if (firebaseConfig && db) {
            handleRoomSelection(roomId);
          } else {
            handleRoomSelectionLocal(roomId);
          }
        });

      // This no-op line left as-is (original code)
      document.getElementById("confirm-reserve-btn");

      async function reserveSeatLocal(seatNumber) {
        console.log("====================================");
        console.log("🔄 reserveSeatLocal CALLED");
        console.log("Seat Number:", seatNumber);
        console.log("Current Room ID:", localCurrentRoom);
        console.log("All Rooms:", localRooms);
        console.log("====================================");
        
        const roomId = localCurrentRoom;
        const seatId = `seat-${seatNumber}`;
        const room = localRooms[roomId];

        if (!room) {
          console.error("❌ ROOM NOT FOUND!");
          showStatusMessage("Room not found!", "error");
          return;
        }

        console.log("✅ Room found:", room);

        const expirationTime = Date.now() + 30 * 60 * 1000;

        if (!room.reservations) {
          console.log("Initializing reservations object");
          room.reservations = {};
        }

        const existing = room.reservations[seatId];
        console.log("Existing reservation?", existing);
        
        if (existing && existing.expires > Date.now()) {
          console.warn("⚠️ Seat already reserved!");
          showStatusMessage("Seat is already reserved!", "error");
          return;
        }

        console.log("Creating new reservation...");
        room.reservations[seatId] = {
          userId: userId,
          reservedAt: Date.now(),
          expires: expirationTime,
        };

        console.log("✅ Reservation created:", room.reservations[seatId]);

        showStatusMessage(
          `Successfully reserved Seat S${seatNumber} for 30 minutes!`,
          "success"
        );

        console.log("Re-rendering seat map...");
        renderSeatMap(room);
        checkUserReservationLocal(room);
        console.log("====================================");
      }

      function cancelSeatLocal(seatNumber) {
        console.log("🗑 cancelSeatLocal CALLED for seat", seatNumber);

        const roomId = localCurrentRoom;
        const room = localRooms[roomId];
        const seatId = `seat-${seatNumber}`;

        if (!room || !room.reservations || !room.reservations[seatId]) {
          console.warn("No reservation found to cancel");
          return;
        }

        delete room.reservations[seatId];

        localRooms[roomId] = room;
        renderSeatMap(localRooms[roomId]);
        checkUserReservationLocal(localRooms[roomId]);

        showStatusMessage(`Reservation for Seat S${seatNumber} has been cancelled.`, "success");
      }

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

      // ---------------------------------------------------------------------
      // Rendering Logic
      // ---------------------------------------------------------------------

      function renderSeatMap(room) {
        console.log("🎨 RENDERING SEAT MAP");
        console.log("Room:", room.name);
        console.log("Reservations:", room.reservations);
        const mapContainer = document.getElementById("seat-map-visualization");
        mapContainer.innerHTML = "";
        document.getElementById("current-room-name").textContent = room.name;

        // Room Info
        const roomInfoDiv = document.createElement("div");
        roomInfoDiv.className =
          "col-span-full mb-4 p-6 bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900 dark:to-blue-900 rounded-lg border border-indigo-200 dark:border-indigo-700";

        const occupancyStatus =
          room.detected_occupancy === 0
            ? '<span class="text-green-600 dark:text-green-400 font-bold">🟢 Available - No one inside</span>'
            : `<span class="text-red-600 dark:text-red-400 font-bold">🔴 Occupied - ${room.detected_occupancy} people detected by AI</span>`;

        roomInfoDiv.innerHTML = `
          <div class="mb-4">
            <p class="text-lg font-semibold mb-2">${occupancyStatus}</p>
            <p class="text-sm text-gray-600 dark:text-gray-300">
              ${room.description || "Study room available for booking"}
            </p>
          </div>

          <div class="flex flex-wrap gap-2 mb-4">
            <span
              class="px-3 py-1 bg-white dark:bg-gray-800 border border-indigo-300 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300 text-sm rounded-full font-medium"
            >
              👥 Capacity: ${room.capacity || room.total_seats} people
            </span>
            ${
              room.has_projector
                ? '<span class="px-3 py-1 bg-white dark:bg-gray-800 border border-indigo-300 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300 text-sm rounded-full font-medium">📽️ Projector</span>'
                : ""
            }
            ${
              room.has_whiteboard
                ? '<span class="px-3 py-1 bg-white dark:bg-gray-800 border border-indigo-300 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300 text-sm rounded-full font-medium">📝 Whiteboard</span>'
                : ""
            }
            ${
              room.has_computers
                ? '<span class="px-3 py-1 bg-white dark:bg-gray-800 border border-indigo-300 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300 text-sm rounded-full font-medium">💻 Computers</span>'
                : ""
            }
          </div>

          <div class="pt-4 border-t border-indigo-200 dark:border-indigo-700">
            ${
              room.detected_occupancy === 0
                ? `<button
                     onclick="showReserveRoomModal('${room.id}')"
                     class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-bold text-lg shadow-lg hover:shadow-xl"
                   >
                     ✓ Reserve This Room
                   </button>`
                : `<button
                     disabled
                     class="w-full px-6 py-3 bg-gray-400 text-white rounded-lg font-bold text-lg cursor-not-allowed opacity-60"
                   >
                     ✗ Room Currently Occupied
                   </button>
                   <p class="text-sm text-red-600 dark:text-red-400 mt-2 text-center">
                     Wait for the room to be empty before reserving
                   </p>`
            }
          </div>
        `;
        mapContainer.appendChild(roomInfoDiv);

        // Occupancy Bar
        const occupancyDiv = document.createElement("div");
        occupancyDiv.className =
          "col-span-full p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700";

        const maxPeople = room.capacity || 12;
        const currentPeople = room.detected_occupancy || 0;
        const percentFull = Math.min((currentPeople / maxPeople) * 100, 100);

        occupancyDiv.innerHTML = `
          <h3 class="text-lg font-bold mb-3">
            Real-Time Occupancy (AI Detection)
          </h3>

          <div class="mb-3">
            <div class="flex justify-between text-sm mb-1">
              <span>People Inside: <strong>${currentPeople}</strong></span>
              <span>Max Capacity: <strong>${maxPeople}</strong></span>
            </div>

            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6">
              <div
                class="h-6 rounded-full transition-all duration-500 flex items-center justify-center text-white text-xs font-bold"
                style="width: ${percentFull}%; background-color: ${
          percentFull === 0
            ? "#10b981"
            : percentFull < 50
            ? "#f59e0b"
            : "#ef4444"
        };"
              >
                ${percentFull > 0 ? `${Math.round(percentFull)}%` : ""}
              </div>
            </div>
          </div>

          <p class="text-xs text-gray-500 dark:text-gray-400">
            📹 Live camera feed monitoring via YOLO AI detection
          </p>
        `;
        mapContainer.appendChild(occupancyDiv);

        // Seats
        const seatsDiv = document.createElement("div");
        seatsDiv.className = "col-span-full";

        const totalSeats = room.total_seats || room.capacity || 12;
        const reservations = room.reservations || {};
        const now = Date.now();

        const seatsHtml = Array.from({ length: totalSeats }, (_, i) => {
          const seatNumber = i + 1;
          const seatKey = `seat-${seatNumber}`;
          const reservation = reservations[seatKey];
          const hasActiveReservation =
            reservation && reservation.expires && reservation.expires > now;
          const reservedByUser =
            hasActiveReservation && reservation.userId === userId;
          const isOccupied = i < currentPeople;
          const isAvailable = !isOccupied && !hasActiveReservation;

          const statusClass = isOccupied
            ? "bg-red-500 text-white"
            : hasActiveReservation
            ? `bg-yellow-400 text-gray-900 ${
                reservedByUser ? "ring-2 ring-indigo-400" : ""
              }`
            : "bg-green-500 text-white hover:bg-green-600 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-300";

          const label = isOccupied
            ? "Occupied"
            : hasActiveReservation
            ? reservedByUser
              ? "Yours"
              : "Reserved"
            : "Available";

          // If available, call window.showConfirmationModal(seatNumber)
          let actionAttr = "";

          // GUEST users → cannot reserve or cancel
          if (isGuestUser) {

            if (isAvailable) {
              // Guest tries to click a green seat: show login popup
              actionAttr = `onclick="window.showGuestRestriction()"`;
            }

            if (reservedByUser) {
              // Guests never reserve, but keep logic consistent
              actionAttr = `onclick="window.showGuestRestriction()"`;
            }

          // LOGGED-IN users
          } else {

            if (isAvailable) {
              // User can reserve
              actionAttr = `onclick="window.showConfirmationModal(${seatNumber})"`;
            }

            if (reservedByUser) {
              // Allow user to cancel their own active reservation
              actionAttr = `onclick="window.showCancelSeatModal(${seatNumber})"`;
            }

          }

          return `
            <button type="button"
              ${actionAttr}
              data-seat-number="${seatNumber}"
              data-seat-available="${isAvailable}"
              class="seat-box h-14 rounded-lg flex flex-col items-center justify-center text-xs font-bold transition ${statusClass}">
              <span>S${seatNumber}</span>
              <span class="text-[10px] font-semibold opacity-90">${label}</span>
            </button>
          `;
        }).join("");


        seatsDiv.innerHTML = `
          <div class="grid grid-cols-6 gap-2 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
            ${seatsHtml}
          </div>
          <p class="text-xs text-center mt-2 text-gray-500">
            Tap a green seat to reserve it for 30 minutes.
          </p>
        `;
        mapContainer.appendChild(seatsDiv);
      }

      // ---------------------------------------------------------------------
      // Room Reservation Modal (whole-room)
      // ---------------------------------------------------------------------

      window.showReserveRoomModal = function (roomId) {
        const room =
          localRooms[roomId] || (firebaseConfig && currentRoomId
            ? { id: currentRoomId }
            : null);

        if (!room) return;

        selectedSeatId = null;

        document.getElementById("modal-title").textContent = "Reserve Room";
        document.getElementById("modal-message").innerHTML = `
          Do you want to reserve <strong>${room.name || "Room " + roomId}</strong>?<br>
          <span class="text-sm text-gray-600 dark:text-gray-300">
            This reserves the entire room for your session.
          </span>
        `;
        document.getElementById("modal-seat-id").textContent =
          room.name || roomId;

        const modal = document.getElementById("confirmation-modal");
        modal.classList.remove("hidden");
        modal.classList.add("flex");

        const confirmBtn = document.getElementById("confirm-reserve-btn");
        confirmBtn.onclick = () => {
          closeModal();
          showStatusMessage(
            `Room ${room.name || roomId} reservation flow not implemented in this demo.`,
            "success"
          );
        };
      };

      // ---------------------------------------------------------------------
      // Reservation Timers (Local + Firebase)
      // ---------------------------------------------------------------------

      function checkUserReservationLocal(room) {
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

      // ---------------------------------------------------------------------
      // Reservation Flow (Firebase mode)
      // ---------------------------------------------------------------------

      async function reserveSeat(seatNumber) {
        if (!isAuthReady) {
          showStatusMessage(
            "Authentication not ready. Please wait...",
            "error"
          );
          return;
        }

        const roomId = currentRoomId;
        if (!roomId) {
          showStatusMessage("Please select a room first.", "error");
          return;
        }

        const roomDocRef = doc(getRoomCollectionRef(), roomId);
        const seatId = `seat-${seatNumber}`;
        const expirationTime = Date.now() + 30 * 60 * 1000;

        try {
          await runTransaction(db, async (transaction) => {
            const roomDoc = await transaction.get(roomDocRef);
            if (!roomDoc.exists()) {
              throw new Error("Room not found");
            }

            const roomData = roomDoc.data();
            const reservations = roomData.reservations || {};
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
            transaction.update(roomDocRef, { reservations });

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

      // ---------------------------------------------------------------------
      // Status Helper
      // ---------------------------------------------------------------------

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

      // ---------------------------------------------------------------------
      // Modal Helpers
      // ---------------------------------------------------------------------

      let pendingReservationType = null; // 'seat' or 'room'
      let pendingReservationTarget = null;

      function showConfirmationModal(seatNumber) {
        console.log("🎯 Opening modal for seat:", seatNumber);
        
        pendingReservationType = 'seat';
        pendingReservationTarget = seatNumber;
        selectedSeatId = seatNumber;

        document.getElementById("modal-title").textContent = "Confirm Seat Reservation";
        document.getElementById("modal-message").innerHTML = `
          Do you want to reserve <strong>Seat S${seatNumber}</strong> for 30 minutes?
        `;
        document.getElementById("modal-seat-id").textContent = `S${seatNumber}`;

        const modal = document.getElementById("confirmation-modal");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
      }
      window.showCancelSeatModal = function (seatNumber) {
        pendingReservationType = "cancel-seat";
        pendingReservationTarget = seatNumber;

        document.getElementById("modal-title").textContent = "Cancel Reservation";
        document.getElementById("modal-message").innerHTML = `
            Do you want to <strong>cancel</strong> your reservation for 
            <strong>Seat S${seatNumber}</strong>?
        `;

        const modal = document.getElementById("confirmation-modal");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
      };

      window.showReserveRoomModal = function (roomId) {
        console.log("🎯 Opening modal for room:", roomId);
        
        pendingReservationType = 'room';
        pendingReservationTarget = roomId;
        
        const room = localRooms[roomId] || (firebaseConfig && currentRoomId ? { id: currentRoomId } : null);
        if (!room) return;

        document.getElementById("modal-title").textContent = "Reserve Room";
        document.getElementById("modal-message").innerHTML = `
          Do you want to reserve <strong>${room.name || "Room " + roomId}</strong>?<br>
          <span class="text-sm text-gray-600 dark:text-gray-300">
            This reserves the entire room for your session.
          </span>
        `;
        document.getElementById("modal-seat-id").textContent = room.name || roomId;

        const modal = document.getElementById("confirmation-modal");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
      };

      function closeModal() {
        const modal = document.getElementById("confirmation-modal");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        selectedSeatId = null;
        pendingReservationType = null;
        pendingReservationTarget = null;
      }

      // Expose to window
      window.showConfirmationModal = showConfirmationModal;
      window.closeModal = closeModal;

      // ---------------------------------------------------------------------
      // Initialization - Attach confirm button ONCE
      // ---------------------------------------------------------------------

      console.log("📱 Attaching confirm button listener (simple)");

      const confirmBtn = document.getElementById("confirm-reserve-btn");

      confirmBtn.addEventListener("click", () => {
        console.log(
          "✅ Confirm clicked! Type:",
          pendingReservationType,
          "Target:",
          pendingReservationTarget
        );

        // 1) Cancel seat
        if (pendingReservationType === "cancel-seat") {
            const seatNum = pendingReservationTarget;
            console.log("🗑 Cancelling reservation for seat", seatNum);
            cancelSeatLocal(seatNum);
            closeModal();
            return;
        }

        if (pendingReservationType === "seat") {
          const seatNum = pendingReservationTarget;
          console.log("➡️ Calling reserveSeatLocal with seat:", seatNum);
          reserveSeatLocal(seatNum);
        } else if (pendingReservationType === "room") {
          console.log("Room reservation not implemented");
          showStatusMessage("Room reservation coming soon!", "success");
        }

        closeModal();
      })

      window.reserveSeatLocal = reserveSeatLocal;
      window.reserveSeat = reserveSeat;

      initFirebase();


    </script>
  </body>
</html>
