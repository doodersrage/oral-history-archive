(function ($) {
	function parseTapeLog(text) {
		const lines = String(text || "").split(/\r?\n/);
		const cues = [];
		const pattern = /^\[?((?:\d{1,2}:)?\d{1,2}:\d{2}(?:\.\d+)?)\]?\s+([^:]+):\s+(.+)$/;
		lines.forEach(function (raw) {
			const line = raw.trim();
			if (!line || line.charAt(0) === "#") {
				return;
			}
			const match = line.match(pattern);
			if (!match) {
				return;
			}
			cues.push({
				time: match[1],
				speaker: match[2].trim(),
				text: match[3].trim(),
			});
		});
		return cues;
	}

	function refreshPreview() {
		const field = document.getElementById("oha_tape_log");
		const preview = document.getElementById("oha-parse-preview");
		const count = document.getElementById("oha-cue-count");
		if (!field || !preview || !count) {
			return;
		}
		const cues = parseTapeLog(field.value);
		count.textContent = String(cues.length);
		if (!field.value.trim()) {
			preview.hidden = true;
			return;
		}
		preview.hidden = false;
		if (!cues.length) {
			preview.textContent = "No cues parsed. Use: 00:00:12 Speaker: text";
			return;
		}
		preview.textContent = cues
			.map(function (cue) {
				return cue.time + "  " + cue.speaker + "  —  " + cue.text;
			})
			.join("\n");
	}

	$(function () {
		const log = document.getElementById("oha_tape_log");
		if (log) {
			log.addEventListener("input", refreshPreview);
			refreshPreview();
		}

		let frame;
		$("#oha-select-audio").on("click", function (event) {
			event.preventDefault();
			if (!wp.media) {
				return;
			}
			if (!frame) {
				frame = wp.media({
					title: "Choose interview recording",
					library: { type: "audio" },
					button: { text: "Use this tape" },
					multiple: false,
				});
				frame.on("select", function () {
					const file = frame.state().get("selection").first().toJSON();
					$("#oha_audio_id").val(file.id);
					$("#oha-audio-label").text(file.filename || file.title);
				});
			}
			frame.open();
		});

		$("#oha-clear-audio").on("click", function (event) {
			event.preventDefault();
			$("#oha_audio_id").val("0");
			$("#oha-audio-label").text("No file attached");
		});
	});
})(jQuery);
