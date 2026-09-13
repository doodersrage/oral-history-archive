(function () {
	const root = document.querySelector("[data-oha-player]");
	if (!root) {
		return;
	}

	const audio = root.querySelector("[data-oha-audio]");
	const playBtn = root.querySelector("[data-oha-play]");
	const seek = root.querySelector("[data-oha-seek]");
	const rate = root.querySelector("[data-oha-rate]");
	const currentEl = root.querySelector("[data-oha-current]");
	const totalEl = root.querySelector("[data-oha-total]");
	const speakerEl = root.querySelector("[data-oha-speaker]");
	const msgEl = root.querySelector("[data-oha-msg]");
	const cues = Array.from(root.querySelectorAll(".oha-cue"));
	const dataNode = document.getElementById("oha-transcript-data");

	let transcript = [];
	try {
		transcript = JSON.parse(dataNode ? dataNode.textContent : "[]");
	} catch (e) {
		transcript = [];
	}

	const startAt = parseFloat(audio.getAttribute("data-start") || "0") || 0;
	const endAt = parseFloat(audio.getAttribute("data-end") || "0") || 0;
	let activeIndex = -1;

	function formatTime(value) {
		const seconds = Math.max(0, value || 0);
		const hours = Math.floor(seconds / 3600);
		const minutes = Math.floor((seconds % 3600) / 60);
		const rest = seconds - hours * 3600 - minutes * 60;
		if (hours > 0) {
			return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${rest.toFixed(2).padStart(5, "0")}`;
		}
		return `${String(minutes).padStart(2, "0")}:${rest.toFixed(2).padStart(5, "0")}`;
	}

	function showMessage(text) {
		if (!msgEl) {
			return;
		}
		if (!text) {
			msgEl.hidden = true;
			msgEl.textContent = "";
			return;
		}
		msgEl.hidden = false;
		msgEl.textContent = text;
	}

	function cueIndexFor(time) {
		let found = -1;
		for (let i = 0; i < transcript.length; i += 1) {
			if (transcript[i].start <= time) {
				found = i;
			} else {
				break;
			}
		}
		return found;
	}

	function setActive(index) {
		if (index === activeIndex) {
			return;
		}
		if (activeIndex >= 0 && cues[activeIndex]) {
			cues[activeIndex].classList.remove("is-active");
		}
		activeIndex = index;
		if (index >= 0 && cues[index]) {
			cues[index].classList.add("is-active");
			const li = cues[index].closest("li");
			if (li && typeof li.scrollIntoView === "function") {
				li.scrollIntoView({ block: "nearest" });
			}
			if (speakerEl && transcript[index]) {
				speakerEl.textContent = transcript[index].speaker;
			}
		}
	}

	function sync() {
		const time = audio.currentTime || 0;
		if (currentEl) {
			currentEl.textContent = formatTime(time);
		}
		if (seek && !seek.matches(":active")) {
			seek.value = String(time);
		}
		setActive(cueIndexFor(time));
		if (endAt > startAt && time >= endAt) {
			audio.pause();
			audio.currentTime = endAt;
			playBtn.textContent = "Play";
		}
		playBtn.textContent = audio.paused ? "Play" : "Pause";
	}

	playBtn.addEventListener("click", function () {
		if (audio.paused) {
			audio.play().catch(function () {
				showMessage("The browser blocked playback. Press Play again.");
			});
		} else {
			audio.pause();
		}
	});

	seek.addEventListener("input", function () {
		audio.currentTime = parseFloat(seek.value) || 0;
		sync();
	});

	rate.addEventListener("change", function () {
		audio.playbackRate = parseFloat(rate.value) || 1;
	});

	cues.forEach(function (button) {
		button.addEventListener("click", function () {
			const t = parseFloat(button.getAttribute("data-start") || "0") || 0;
			jumpTo(t);
		});
	});

	function jumpTo(t) {
		const apply = function () {
			const start = audio.play();
			const seek = function () {
				audio.currentTime = t;
				sync();
			};
			if (start && typeof start.then === "function") {
				start.then(seek).catch(seek);
			} else {
				seek();
			}
		};
		if (audio.readyState >= 1 && isFinite(audio.duration) && audio.duration > 0) {
			apply();
			return;
		}
		audio.addEventListener("canplay", apply, { once: true });
		if (audio.readyState < 1) {
			audio.load();
		}
	}

	audio.addEventListener("loadedmetadata", function () {
		if (seek) {
			seek.max = String(audio.duration || seek.max);
		}
		if (totalEl && audio.duration) {
			totalEl.textContent = formatTime(audio.duration);
		}
		if (startAt > 0) {
			audio.addEventListener(
				"play",
				function seekStart() {
					audio.currentTime = startAt;
					audio.removeEventListener("play", seekStart);
					sync();
				}
			);
		}
		sync();
	});

	audio.addEventListener("timeupdate", sync);
	audio.addEventListener("play", sync);
	audio.addEventListener("pause", sync);
	audio.addEventListener("waiting", function () {
		showMessage("Loading tape…");
	});
	audio.addEventListener("canplay", function () {
		showMessage("");
	});
	audio.addEventListener("error", function () {
		showMessage("The recording could not be loaded.");
	});

	document.addEventListener("keydown", function (event) {
		if (event.target && ["INPUT", "TEXTAREA", "SELECT", "BUTTON"].includes(event.target.tagName)) {
			if (event.target !== playBtn) {
				return;
			}
		}
		if (event.code === "Space" && event.target === document.body) {
			event.preventDefault();
			playBtn.click();
		}
	});
})();
