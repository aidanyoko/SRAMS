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
    <!-- Firebase Configuration (Injected from Laravel) -->
    <script>
        console.log("🔥 appId from Laravel =", "{{ config('firebase.institution_id') }}");

        @if(config('firebase.enabled'))
            window.__firebase_config = @json(config('firebase.config'));
            window.__app_id = "{{ config('firebase.institution_id') }}";
            window.__initial_auth_token = null;
        @else
            window.__firebase_config = null;
            window.__app_id = "local-mode";
            window.__initial_auth_token = null;
        @endif
    </script>


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
        @auth
          <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-xs">
          <p class="font-semibold mb-1">Manage Rooms:</p>
          <a
            href="http://127.0.0.1:9000/classroom"
            target="_blank"
            class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
          >
            <span>→</span>
            Add/Edit rooms in Python system
          </a>
        </div>
        @endauth
        

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
        getDoc,          // ← ADD THIS
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
            ? __firebase_config
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
      const PYTHON_API_URL = "http://127.0.0.1:9000";
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

            await loadRoomsLocally();
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
              '<a href="http://127.0.0.1:9000/classroom" target="_blank" class="text-indigo-600 hover:underline">Create rooms in Python API →</a>';

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

          // ✨ NEW: Sync to Firebase if enabled
          if (firebaseConfig && db) {
            await syncRoomsToFirebase(roomKeys, counts);
          }

          if (roomKeys.length > 0) {
            dropdown.value = roomKeys[0];
            if (firebaseConfig && db) {
              handleRoomSelection(roomKeys[0]);
            } else {
              handleRoomSelectionLocal(roomKeys[0]);
            }
          }

          document.getElementById("loading-indicator").style.display = "none";
        } catch (error) {
          console.error("Failed to load rooms from Python API:", error);

          dropdown.innerHTML =
            '<option value="" disabled selected>Error: Python API not running</option>';

          document.getElementById("loading-indicator").innerHTML =
            '<div class="text-red-600">' +
            "Python API not found. <br>" +
            '<a href="http://127.0.0.1:9000/classroom" target="_blank" class="text-indigo-600 hover:underline">Start Python server and create rooms →</a>' +
            "</div>";
        }
      }

      /**
 * Sync rooms from Python API to Firebase
 */
      async function syncRoomsToFirebase(roomIds, occupancyCounts) {
        console.log("🔄 Syncing rooms to Firebase...");

        try {
          for (const roomId of roomIds) {
            const roomDocRef = doc(getRoomCollectionRef(), roomId);
            
            // Check if room exists in Firebase
            const roomSnapshot = await getDoc(roomDocRef);
            
            if (!roomSnapshot.exists()) {
              // Room doesn't exist - CREATE it
              console.log(`✨ Creating new room in Firebase: ${roomId}`);
              
              const newRoomData = {
                id: roomId,
                name: getRoomName(roomId),
                description: getRoomDescription(roomId),
                room_number: roomId,
                slug: roomId.toLowerCase(),
                total_seats: 12,
                seat_capacity: 12,
                capacity: 12,
                detected_occupancy: occupancyCounts[roomId] || 0,
                has_projector: getRoomFeature(roomId, "projector"),
                has_whiteboard: getRoomFeature(roomId, "whiteboard"),
                has_computers: getRoomFeature(roomId, "computer"),
                reservations: {},
                is_available: (occupancyCounts[roomId] || 0) === 0,
              };
              
              await setDoc(roomDocRef, newRoomData);
              console.log(`✅ Room ${roomId} created in Firebase`);
              
            } else {
              // Room exists - UPDATE occupancy only
              const currentData = roomSnapshot.data();
              
              await setDoc(roomDocRef, {
                ...currentData,
                detected_occupancy: occupancyCounts[roomId] || 0,
                is_available: (occupancyCounts[roomId] || 0) === 0,
              }, { merge: true });
              
              console.log(`✅ Room ${roomId} occupancy updated in Firebase`);
            }
          }
          
          console.log("✅ All rooms synced to Firebase");
        } catch (error) {
          console.error("❌ Error syncing rooms to Firebase:", error);
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

          // ===== LOCAL MODE =====
          if (!firebaseConfig || !db) {
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
          // ===== FIREBASE MODE =====
          else {
            console.log("🔄 Updating Firebase with Python API occupancy data...");
            
            // Update each room's occupancy in Firebase
            const updatePromises = Object.keys(counts).map(async (roomId) => {
              const roomDocRef = doc(getRoomCollectionRef(), roomId);
              const occupancyCount = counts[roomId];
              
              try {
                // Check if room exists first
                const roomSnapshot = await getDoc(roomDocRef);
                
                if (!roomSnapshot.exists()) {
                  // Room doesn't exist in Firebase yet - create it
                  console.log(`✨ Creating room ${roomId} in Firebase`);
                  
                  const newRoomData = {
                    id: roomId,
                    name: getRoomName(roomId),
                    description: getRoomDescription(roomId),
                    room_number: roomId,
                    slug: roomId.toLowerCase(),
                    total_seats: 12,
                    seat_capacity: 12,
                    capacity: 12,
                    detected_occupancy: occupancyCount,
                    has_projector: getRoomFeature(roomId, "projector"),
                    has_whiteboard: getRoomFeature(roomId, "whiteboard"),
                    has_computers: getRoomFeature(roomId, "computer"),
                    reservations: {},
                    is_available: occupancyCount === 0,
                  };
                  
                  await setDoc(roomDocRef, newRoomData);
                } else {
                  // Room exists - update occupancy only (don't touch reservations)
                  await setDoc(roomDocRef, {
                    detected_occupancy: occupancyCount,
                    is_available: occupancyCount === 0,
                  }, { merge: true });
                  
                  console.log(`✅ Room ${roomId} occupancy: ${occupancyCount}`);
                }
              } catch (error) {
                console.warn(`⚠️ Could not update room ${roomId}:`, error.message);
              }
            });
            
            // Wait for all updates to complete
            await Promise.all(updatePromises);
            console.log("✅ Firebase occupancy sync complete");
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

      async function syncAIToFirebase(counts) {
        console.log("🔄 Syncing AI detections to Firebase...");

        const updates = Object.entries(counts).map(async ([roomId, count]) => {
          const roomRef = doc(getRoomCollectionRef(), roomId);

          // Read existing room
          const snap = await getDoc(roomRef);

          // If room does NOT exist → create it
          if (!snap.exists()) {
            console.log(`✨ Creating missing room in Firebase: ${roomId}`);

            await setDoc(roomRef, {
              id: roomId,
              name: getRoomName(roomId),
              description: getRoomDescription(roomId),
              total_seats: 12,
              reservations: {},
              detected_occupancy: count,
              is_available: count === 0,
            });

            return;
          }

          // If room exists → update occupancy only
          await setDoc(
            roomRef,
            {
              detected_occupancy: count,
              is_available: count === 0,
            },
            { merge: true }
          );
        });

        await Promise.all(updates);

        console.log("✅ Firebase occupancy updated successfully.");
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
          
          // Show occupancy in dropdown
          const occupancy = room.detected_occupancy || 0;
          const status = occupancy === 0 ? "🟢" : `🔴 ${occupancy} people`;
          
          option.textContent = `${room.name} - ${status}`;
          dropdown.appendChild(option);
        });

        if (rooms.length > 0) {
          dropdown.value = rooms[0].id;
          handleRoomSelection(rooms[0].id);
        }

        document.getElementById("loading-indicator").style.display = "none";
        
        // ✨ NEW: Start AI polling immediately after loading rooms
        console.log("🎬 Starting Python API polling for Firebase mode...");
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

      async function handleRoomSelection(roomId) {
          console.log("📌 Selecting room:", roomId);

          currentRoomId = roomId; 

          // 1️⃣ Ensure Firebase document exists before listening
          if (firebaseConfig && db) {
              const roomRef = doc(getRoomCollectionRef(), roomId);
              const snap = await getDoc(roomRef);

              if (!snap.exists()) {
                  console.log("⚠️ Room missing in Firebase. Creating now...");

                  await setDoc(roomRef, {
                      id: roomId,
                      name: getRoomName(roomId),
                      description: getRoomDescription(roomId),
                      total_seats: 12,
                      reservations: {},
                      detected_occupancy: 0,
                      is_available: true,
                  });
              }
          }

          // 2️⃣ NOW attach the listener (the room definitely exists)
          listenToRoomData(roomId);
      }

      let latestRoomSnapshot = null;

      function listenToRoomData(roomId) {
        const roomRef = doc(getRoomCollectionRef(), roomId);

        if (unsubscribeRoomListener) {
          unsubscribeRoomListener();
        }

        unsubscribeRoomListener = onSnapshot(
          roomRef,
          (snap) => {
            if (!snap.exists()) {
              console.log(`⏳ Room ${roomId} not found in Firebase yet... waiting`);

              // Do NOT show an error. Firebase will update shortly.
              document.getElementById("current-room-name").textContent = `Loading...`;

              // Just wait—syncAIToFirebase will create it soon
              return;
            }

            // ✔ Room NOW exists!
            latestRoomSnapshot = { id: snap.id, ...snap.data() };

            console.log("🔥 Firebase snapshot received:", latestRoomSnapshot);

            document.getElementById("current-room-name").textContent =
              latestRoomSnapshot.name;

            renderSeatMap(latestRoomSnapshot);
            checkUserReservation(latestRoomSnapshot);
          },
          (err) => {
            console.error("Snapshot error:", err);
          }
        );
      }

      // ---------------------------------------------------------------------
      // Rendering Logic
      // ---------------------------------------------------------------------

      function renderSeatMap(room) {
        console.log("🎨 RENDERING SEAT MAP");
        console.log("Room:", room.name);
        console.log("AI Detected Occupancy:", room.detected_occupancy); // ← Add this log
        console.log("Reservations:", room.reservations);
        
        const mapContainer = document.getElementById("seat-map-visualization");
        mapContainer.innerHTML = "";
        document.getElementById("current-room-name").textContent = room.name;

        // Occupancy Bar
        const occupancyDiv = document.createElement("div");
        occupancyDiv.className =
          "col-span-full p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700";

        const maxPeople = room.capacity || 12;
        const currentPeople = room.detected_occupancy || 0; // ← AI detection value
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
          
          // ✨ Mark seat as occupied if AI detected someone AND seat number is within occupancy count
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

          let actionAttr = "";

          if (isGuestUser) {
            if (isAvailable) {
              actionAttr = `onclick="window.showGuestRestriction()"`;
            }
            if (reservedByUser) {
              actionAttr = `onclick="window.showGuestRestriction()"`;
            }
          } else {
            if (isAvailable) {
              actionAttr = `onclick="window.showConfirmationModal(${seatNumber})"`;
            }
            if (reservedByUser) {
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
          const timerEl = document.getElementById("reservation-timer");

          clearInterval(timerEl.timerInterval);

          const userRes = Object.keys(reservations).find(
              (key) => {
                  const r = reservations[key];
                  return r &&
                        r.userId === userId &&
                        r.expires &&
                        Number(r.expires) > Date.now();
              }
          );

          if (!userRes) {
              timerEl.innerHTML = "N/A";
              return;
          }

          let expires = reservations[userRes].expires;

          // Ensure expires is a number
          expires = Number(expires);
          if (isNaN(expires)) {
              timerEl.innerHTML = "N/A";
              return;
          }

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
       }


      function checkUserReservation(room) {
          const reservations = room.reservations || {};
          const timerEl = document.getElementById("reservation-timer");

          clearInterval(timerEl.timerInterval);

          const userRes = Object.keys(reservations).find(
              (key) => {
                  const r = reservations[key];
                  return r &&
                        r.userId === userId &&
                        r.expires &&
                        Number(r.expires) > Date.now();
              }
          );

          if (!userRes) {
              timerEl.innerHTML = "N/A";
              return;
          }

          let expires = reservations[userRes].expires;

          // Firestore sometimes returns Timestamp
          if (typeof expires === "object" && expires.toMillis) {
              expires = expires.toMillis();
          } else {
              expires = Number(expires);
          }

          if (isNaN(expires)) {
              timerEl.innerHTML = "N/A";
              return;
          }

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
            let roomDoc = await transaction.get(roomDocRef);

            // If room does NOT exist → CREATE IT instead of throwing an error
            if (!roomDoc.exists()) {
              console.warn("⚠️ Room missing in Firebase. Creating it now...");

              transaction.set(roomDocRef, {
                id: roomId,
                name: getRoomName(roomId),
                description: getRoomDescription(roomId),
                total_seats: 12,
                reservations: {},
                detected_occupancy: 0,
                is_available: true,
              });

              // After creating, simulate roomDoc for the rest of the transaction
              roomDoc = {
                exists: () => true,
                data: () => ({ reservations: {} }),
              };
            }

            const roomData = roomDoc.data();
            const reservations = roomData.reservations || {};
            const currentReservation = reservations[seatId];

            if (currentReservation && currentReservation.expires > Date.now()) {
              throw new Error("Seat was just reserved by another user!");
            }

            reservations[seatId] = {
              userId: userId,
              reservedAt: serverTimestamp(),
              expires: expirationTime,
            };

            transaction.update(roomDocRef, { reservations });
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

      async function cancelSeat(seatNumber) {
        if (!isAuthReady) {
            showStatusMessage("Authentication not ready. Please wait...", "error");
            return;
        }

        const roomId = currentRoomId;
        if (!roomId) {
            showStatusMessage("Please select a room first.", "error");
            return;
        }

        const roomDocRef = doc(getRoomCollectionRef(), roomId);
        const seatId = `seat-${seatNumber}`;

        try {
            await runTransaction(db, async (transaction) => {
                const roomDoc = await transaction.get(roomDocRef);

                if (!roomDoc.exists()) {
                    throw new Error("Room not found");
                }

                const data = roomDoc.data();
                const reservations = data.reservations || {};

                const existing = reservations[seatId];

                if (!existing || existing.userId !== userId) {
                    throw new Error("You cannot cancel this reservation.");
                }

                // Remove seat
                delete reservations[seatId];

                transaction.update(roomDocRef, { reservations });
            });

            showStatusMessage(`Reservation for S${seatNumber} cancelled.`, "success");

        } catch (e) {
            console.error("Cancel failed:", e);
            showStatusMessage("Unable to cancel reservation.", "error");
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

        // 1️⃣ Cancel a seat
        if (pendingReservationType === "cancel-seat") {
            const seatNum = pendingReservationTarget;

            if (firebaseConfig && db) {
                console.log("🗑 Cancelling seat in FIREBASE:", seatNum);
                cancelSeat(seatNum);
            } else {
                console.log("🗑 Cancelling seat in LOCAL MODE:", seatNum);
                cancelSeatLocal(seatNum);
            }

            closeModal();
            return;
        }

        // 2️⃣ Reserve a seat
        if (pendingReservationType === "seat") {
            const seatNum = pendingReservationTarget;

            if (firebaseConfig && db) {
                console.log("➡️ Calling reserveSeat (FIREBASE) with seat:", seatNum);
                reserveSeat(seatNum);
            } else {
                console.log("➡️ Calling reserveSeatLocal (LOCAL MODE) with seat:", seatNum);
                reserveSeatLocal(seatNum);
            }

            closeModal();
            return;
        }

        // 3️⃣ Reserve room (future)
        if (pendingReservationType === "room") {
            console.log("Room reservation not implemented");
            showStatusMessage("Room reservation coming soon!", "success");
            closeModal();
            return;
        }

        closeModal();
    });

      window.reserveSeatLocal = reserveSeatLocal;
      window.reserveSeat = reserveSeat;

      initFirebase();


    </script>
  </body>
</html>