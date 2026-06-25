from __future__ import annotations

import sys
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path
from time import perf_counter

import cv2

from PyQt5.QtCore import QEvent, QTimer, Qt
from PyQt5.QtGui import QImage, QKeyEvent, QPixmap
from PyQt5.QtWidgets import (
	QApplication,
	QCheckBox,
	QComboBox,
	QDoubleSpinBox,
	QFormLayout,
	QGridLayout,
	QGroupBox,
	QHBoxLayout,
	QLabel,
	QMainWindow,
	QPushButton,
	QSpinBox,
	QVBoxLayout,
	QWidget,
)
PYQT_VERSION = 5


@dataclass
class CameraConfig:
	camera_index: int = 0
	width: int = 1280
	height: int = 720
	fps: int = 30
	shutter_speed: float = -13.0
	iso: int = 100
	auto_exposure: bool = True
	auto_focus: bool = False
	output_dir: Path = Path(__file__).resolve().parent / "captures"


CONFIG = CameraConfig()


def open_capture_with_fallback(camera_index: int, *, allow_default_backend: bool = True) -> cv2.VideoCapture:
	if not sys.platform.startswith("win"):
		return cv2.VideoCapture(camera_index)

	backends = [
		getattr(cv2, "CAP_MSMF", None),
	]

	for backend in backends:
		if backend is None:
			continue
		capture = cv2.VideoCapture(camera_index, backend)
		if capture.isOpened():
			return capture
		capture.release()

	if allow_default_backend:
		return cv2.VideoCapture(camera_index)

	return cv2.VideoCapture()


def build_capture() -> cv2.VideoCapture:
	capture = open_capture_with_fallback(CONFIG.camera_index)

	if not capture.isOpened():
		raise RuntimeError(f"Tidak bisa membuka kamera dengan index {CONFIG.camera_index}.")

	capture.set(cv2.CAP_PROP_FRAME_WIDTH, CONFIG.width)
	capture.set(cv2.CAP_PROP_FRAME_HEIGHT, CONFIG.height)
	capture.set(cv2.CAP_PROP_FPS, CONFIG.fps)

	preferred_fourcc = cv2.VideoWriter_fourcc(*"MJPG")
	capture.set(cv2.CAP_PROP_FOURCC, preferred_fourcc)

	if CONFIG.auto_exposure:
		capture.set(cv2.CAP_PROP_AUTO_EXPOSURE, 0.75)
	else:
		capture.set(cv2.CAP_PROP_AUTO_EXPOSURE, 0.25)
		capture.set(cv2.CAP_PROP_EXPOSURE, CONFIG.shutter_speed)

	iso_prop = getattr(cv2, "CAP_PROP_ISO_SPEED", None)
	if iso_prop is not None and not CONFIG.auto_exposure:
		capture.set(iso_prop, CONFIG.iso)

	gain_prop = getattr(cv2, "CAP_PROP_GAIN", None)
	if gain_prop is not None and not CONFIG.auto_exposure and CONFIG.iso is not None:
		capture.set(gain_prop, CONFIG.iso)

	auto_wb_prop = getattr(cv2, "CAP_PROP_AUTO_WB", None)
	if auto_wb_prop is not None:
		capture.set(auto_wb_prop, 1)

	autofocus_prop = getattr(cv2, "CAP_PROP_AUTOFOCUS", None)
	if autofocus_prop is not None:
		capture.set(autofocus_prop, 1 if CONFIG.auto_focus else 0)

	return capture


def list_camera_indices(max_index: int = 10) -> list[int]:
	indices = []
	for index in range(max_index):
		probe = open_capture_with_fallback(index)
		if probe.isOpened():
			indices.append(index)
		probe.release()

	if not indices:
		indices = [CONFIG.camera_index]

	return indices


class CameraWindow(QMainWindow):
	def __init__(self) -> None:
		super().__init__()
		self.available_camera_indices = list_camera_indices()
		self.capture = build_capture()
		self.output_dir = CONFIG.output_dir
		self.output_dir.mkdir(parents=True, exist_ok=True)

		self.space_held = False
		self.captured_count = 0
		self.current_fps = 0.0
		self.fps_frame_count = 0
		self.fps_window_start = perf_counter()

		self.setWindowTitle("PyQt Camera Control")
		self.resize(1280, 920)
		self._install_theme()
		self._build_ui()
		self._connect_events()
		self.setFocusPolicy(self._strong_focus_policy())
		self.single_capture_feedback_timer = QTimer(self)
		self.single_capture_feedback_timer.setSingleShot(True)
		self.single_capture_feedback_timer.timeout.connect(self._clear_single_capture_feedback)
		self.apply_exposure_settings()
		self.refresh_status()
		self._update_preview_border_feedback()

		app = QApplication.instance()
		if app is not None:
			app.installEventFilter(self)

		self.frame_timer = QTimer(self)
		self.frame_timer.timeout.connect(self.update_frame)
		self.frame_timer.start(15)

	def _install_theme(self) -> None:
		self.setStyleSheet(
			"""
			QMainWindow, QWidget {
				background: #111827;
				color: #e5e7eb;
			}
			QGroupBox {
				border: 1px solid #374151;
				border-radius: 10px;
				margin-top: 12px;
				padding-top: 12px;
				font-weight: 600;
			}
			QGroupBox::title {
				subcontrol-origin: margin;
				left: 12px;
				padding: 0 4px;
			}
			QLabel#previewLabel {
				background: #030712;
				border: 1px solid #374151;
				border-radius: 12px;
			}
			QPushButton {
				background: #2563eb;
				color: #f9fafb;
				border: none;
				border-radius: 8px;
				padding: 8px 14px;
			}
			QPushButton:hover {
				background: #1d4ed8;
			}
			QPushButton:pressed {
				background: #0f766e;
			}
			QComboBox, QSpinBox, QDoubleSpinBox {
				background: #1f2937;
				border: 1px solid #4b5563;
				border-radius: 6px;
				padding: 6px 8px;
				color: #f9fafb;
			}
			QCheckBox {
				spacing: 8px;
			}
			QLabel#statusLabel {
				background: #0f172a;
				border: 1px solid #334155;
				border-radius: 10px;
				padding: 12px;
			}
			"""
		)

	def _build_ui(self) -> None:
		central_widget = QWidget(self)
		main_layout = QVBoxLayout(central_widget)
		main_layout.setContentsMargins(16, 16, 16, 16)
		main_layout.setSpacing(12)

		controls_group = QGroupBox("Kontrol Kamera")
		controls_layout = QGridLayout(controls_group)
		controls_layout.setHorizontalSpacing(16)
		controls_layout.setVerticalSpacing(12)

		exposure_form = QFormLayout()
		exposure_form.setVerticalSpacing(9)

		self.shutter_spin = QDoubleSpinBox()
		self.shutter_spin.setRange(-13.0, -1.0)
		self.shutter_spin.setSingleStep(0.1)
		self.shutter_spin.setDecimals(1)
		self.shutter_spin.setValue(CONFIG.shutter_speed)
		exposure_form.addRow("Shutter Speed", self.shutter_spin)

		self.iso_spin = QSpinBox()
		self.iso_spin.setRange(50, 3200)
		self.iso_spin.setSingleStep(50)
		self.iso_spin.setValue(CONFIG.iso)
		exposure_form.addRow("ISO", self.iso_spin)
  
		self.auto_exposure_check = QCheckBox("Auto Exposure")
		self.auto_exposure_check.setChecked(CONFIG.auto_exposure)
		exposure_form.addRow("Exposure", self.auto_exposure_check)

		controls_layout.addLayout(exposure_form, 0, 0)

		resolution_form = QFormLayout()
		resolution_form.setVerticalSpacing(9)
  
		self.width_spin = QSpinBox()
		self.width_spin.setRange(1, 8192)
		self.width_spin.setValue(CONFIG.width)
		resolution_form.addRow("Res Width", self.width_spin)

		self.height_spin = QSpinBox()
		self.height_spin.setRange(1, 8192)
		self.height_spin.setValue(CONFIG.height)
		resolution_form.addRow("Res Height", self.height_spin)

		self.camera_combo = QComboBox()
		for index in self.available_camera_indices:
			self.camera_combo.addItem(f"Camera {index}", index)
		self._set_current_camera_combo(CONFIG.camera_index)
		resolution_form.addRow("Cam Index", self.camera_combo)

		controls_layout.addLayout(resolution_form, 0, 1)

		actions_layout = QVBoxLayout()
		actions_layout.setSpacing(10)
		self.apply_resolution_button = QPushButton("Apply Resolution")
		self.capture_button = QPushButton("Capture Sekali (C)")
		self.burst_button = QPushButton("Burst Capture (Space)")
		actions_layout.addWidget(self.apply_resolution_button)
		actions_layout.addWidget(self.capture_button)
		actions_layout.addWidget(self.burst_button)
		actions_layout.addStretch(1)

		controls_layout.addLayout(actions_layout, 0, 2)

		self.preview_label = QLabel("Menunggu frame kamera...")
		self.preview_label.setObjectName("previewLabel")
		self.preview_label.setAlignment(self._align_center())
		self.preview_label.setMinimumSize(960, 540)

		self.status_label = QLabel()
		self.status_label.setObjectName("statusLabel")
		self.status_label.setWordWrap(True)
		self.status_label.setAlignment(self._align_top_left())

		main_layout.addWidget(controls_group)
		main_layout.addWidget(self.preview_label, 1)
		main_layout.addWidget(self.status_label)

		self.setCentralWidget(central_widget)

	def _connect_events(self) -> None:
		self.camera_combo.currentIndexChanged.connect(self.on_camera_change)
		self.apply_resolution_button.clicked.connect(self.apply_resolution)
		self.capture_button.clicked.connect(self.capture_once)
		self.burst_button.pressed.connect(self.start_burst_capture)
		self.burst_button.released.connect(self.stop_burst_capture)
		self.shutter_spin.valueChanged.connect(self.on_shutter_change)
		self.iso_spin.valueChanged.connect(self.on_iso_change)
		self.auto_exposure_check.toggled.connect(self.on_auto_exposure_toggle)

	def _set_current_camera_combo(self, camera_index: int) -> None:
		for combo_index in range(self.camera_combo.count()):
			if self.camera_combo.itemData(combo_index) == camera_index:
				self.camera_combo.setCurrentIndex(combo_index)
				return

	def _align_center(self):
		if PYQT_VERSION == 6:
			return Qt.AlignmentFlag.AlignCenter
		return Qt.AlignCenter

	def _align_top_left(self):
		if PYQT_VERSION == 6:
			return Qt.AlignmentFlag.AlignTop | Qt.AlignmentFlag.AlignLeft
		return Qt.AlignTop | Qt.AlignLeft

	def _keyboard_key(self, event: QKeyEvent):
		return event.key()

	def _space_key(self):
		if PYQT_VERSION == 6:
			return Qt.Key.Key_Space
		return Qt.Key_Space

	def _c_key(self):
		if PYQT_VERSION == 6:
			return Qt.Key.Key_C
		return Qt.Key_C

	def _q_key(self):
		if PYQT_VERSION == 6:
			return Qt.Key.Key_Q
		return Qt.Key_Q

	def _strong_focus_policy(self):
		if PYQT_VERSION == 6:
			return Qt.FocusPolicy.StrongFocus
		return Qt.StrongFocus

	def _key_press_event_type(self):
		if PYQT_VERSION == 6:
			return QEvent.Type.KeyPress
		return QEvent.KeyPress

	def _key_release_event_type(self):
		if PYQT_VERSION == 6:
			return QEvent.Type.KeyRelease
		return QEvent.KeyRelease

	def build_status_text(self) -> str:
		exposure_mode = "AUTO" if CONFIG.auto_exposure else "MANUAL"
		burst_state = "ON" if self.space_held else "OFF"
		return (
			f"Kamera: {CONFIG.camera_index} | Resolusi: {CONFIG.width}x{CONFIG.height} | FPS: {self.current_fps:.1f}\n"
			f"Exposure: {exposure_mode} | Shutter: {CONFIG.shutter_speed:.1f} | ISO: {CONFIG.iso}\n"
			f"Burst capture: {burst_state} | Total tersimpan: {self.captured_count}\n"
			f"Simpan ke: {self.output_dir}"
		)

	def refresh_status(self) -> None:
		self.status_label.setText(self.build_status_text())

	def _set_preview_border(self, color: str, width: int) -> None:
		self.preview_label.setStyleSheet(
			f"background: #030712; border: {width}px solid {color}; border-radius: 12px;"
		)

	def _update_preview_border_feedback(self) -> None:
		if self.space_held:
			self._set_preview_border("#facc15", 3)
			return
		self._set_preview_border("#374151", 1)

	def _flash_single_capture_feedback(self) -> None:
		if self.space_held:
			return
		self._set_preview_border("#22c55e", 3)
		self.single_capture_feedback_timer.start(220)

	def _clear_single_capture_feedback(self) -> None:
		self._update_preview_border_feedback()

	def start_burst_capture(self) -> None:
		if self.single_capture_feedback_timer.isActive():
			self.single_capture_feedback_timer.stop()
		self.space_held = True
		self._update_preview_border_feedback()
		self.refresh_status()

	def stop_burst_capture(self) -> None:
		self.space_held = False
		self._update_preview_border_feedback()
		self.refresh_status()

	def on_camera_change(self, combo_index: int) -> None:
		selected_index = self.camera_combo.itemData(combo_index)
		if selected_index is None or selected_index == CONFIG.camera_index:
			return

		if self.capture is not None and self.capture.isOpened():
			self.capture.release()

		CONFIG.camera_index = int(selected_index)
		self.capture = build_capture()
		self.apply_exposure_settings()
		self.fps_frame_count = 0
		self.fps_window_start = perf_counter()
		self.current_fps = 0.0
		self.refresh_status()

	def apply_resolution(self) -> None:
		new_width = int(self.width_spin.value())
		new_height = int(self.height_spin.value())
		CONFIG.width = new_width
		CONFIG.height = new_height
		self.capture.set(cv2.CAP_PROP_FRAME_WIDTH, new_width)
		self.capture.set(cv2.CAP_PROP_FRAME_HEIGHT, new_height)
		self.refresh_status()

	def on_shutter_change(self, value: float) -> None:
		CONFIG.shutter_speed = float(value)
		self.apply_exposure_settings()
		self.refresh_status()

	def on_iso_change(self, value: int) -> None:
		CONFIG.iso = int(value)
		self.apply_exposure_settings()
		self.refresh_status()

	def on_auto_exposure_toggle(self, checked: bool) -> None:
		CONFIG.auto_exposure = checked
		self.apply_exposure_settings()
		self.refresh_status()

	def apply_exposure_settings(self) -> None:
		if CONFIG.auto_exposure:
			self.capture.set(cv2.CAP_PROP_AUTO_EXPOSURE, 0.75)
			return

		self.capture.set(cv2.CAP_PROP_AUTO_EXPOSURE, 0.25)
		self.capture.set(cv2.CAP_PROP_EXPOSURE, CONFIG.shutter_speed)

		iso_prop = getattr(cv2, "CAP_PROP_ISO_SPEED", None)
		if iso_prop is not None:
			self.capture.set(iso_prop, CONFIG.iso)

		gain_prop = getattr(cv2, "CAP_PROP_GAIN", None)
		if gain_prop is not None:
			self.capture.set(gain_prop, CONFIG.iso)

	def save_frame(self, frame) -> None:
		timestamp = datetime.now().strftime("%Y%m%d_%H%M%S_%f")
		file_path = self.output_dir / f"capture_{timestamp}.jpg"
		cv2.imwrite(str(file_path), frame)
		self.captured_count += 1
		self.refresh_status()

	def capture_once(self) -> None:
		if self.capture is None or not self.capture.isOpened():
			return

		ok, frame = self.capture.read()
		if ok:
			self.save_frame(frame)
			self._flash_single_capture_feedback()

	def _set_preview_frame(self, frame) -> None:
		rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
		height, width, channels = rgb_frame.shape
		bytes_per_line = channels * width
		image = QImage(rgb_frame.data, width, height, bytes_per_line, QImage.Format.Format_RGB888 if PYQT_VERSION == 6 else QImage.Format_RGB888)
		pixmap = QPixmap.fromImage(image)
		scaled = pixmap.scaled(
			self.preview_label.size(),
			self._keep_aspect_ratio_mode(),
			self._smooth_transformation_mode(),
		)
		self.preview_label.setPixmap(scaled)

	def _keep_aspect_ratio_mode(self):
		if PYQT_VERSION == 6:
			return Qt.AspectRatioMode.KeepAspectRatio
		return Qt.KeepAspectRatio

	def _smooth_transformation_mode(self):
		if PYQT_VERSION == 6:
			return Qt.TransformationMode.SmoothTransformation
		return Qt.SmoothTransformation

	def update_frame(self) -> None:
		if self.capture is None or not self.capture.isOpened():
			self.close()
			return

		ok, frame = self.capture.read()
		if not ok:
			return

		if self.space_held:
			self.save_frame(frame)

		self.fps_frame_count += 1
		now = perf_counter()
		elapsed = now - self.fps_window_start
		if elapsed >= 1.0:
			self.current_fps = self.fps_frame_count / elapsed
			self.fps_window_start = now
			self.fps_frame_count = 0

		self.refresh_status()

		display_frame = frame
		preview_width = 960
		if display_frame.shape[1] > preview_width:
			scale = preview_width / display_frame.shape[1]
			new_size = (preview_width, max(1, int(display_frame.shape[0] * scale)))
			display_frame = cv2.resize(display_frame, new_size, interpolation=cv2.INTER_AREA)

		self._set_preview_frame(display_frame)

	def keyPressEvent(self, event: QKeyEvent) -> None:
		if event.isAutoRepeat():
			event.ignore()
			return

		key = self._keyboard_key(event)
		if key == self._space_key():
			self.start_burst_capture()
			event.accept()
			return

		if key == self._c_key():
			self.capture_once()
			event.accept()
			return

		if key == self._q_key():
			self.close()
			event.accept()
			return

		super().keyPressEvent(event)

	def keyReleaseEvent(self, event: QKeyEvent) -> None:
		if event.isAutoRepeat():
			event.ignore()
			return

		if self._keyboard_key(event) == self._space_key():
			self.stop_burst_capture()
			event.accept()
			return

		super().keyReleaseEvent(event)

	def changeEvent(self, event: QEvent) -> None:
		if event.type() == self._activation_change_event() and self.isActiveWindow():
			self.activateWindow()
			event.accept()
			super().changeEvent(event)
			return

		super().changeEvent(event)

	def _activation_change_event(self):
		if PYQT_VERSION == 6:
			return QEvent.Type.ActivationChange
		return QEvent.ActivationChange

	def eventFilter(self, watched, event) -> bool:
		if not self.isVisible() or not self.isActiveWindow():
			return super().eventFilter(watched, event)

		event_type = event.type()
		if event_type == self._key_press_event_type():
			key_event = event
			if key_event.isAutoRepeat():
				return False

			key = key_event.key()
			if key == self._space_key():
				self.start_burst_capture()
				return True

			if key == self._c_key():
				self.capture_once()
				return True

			if key == self._q_key():
				self.close()
				return True

		if event_type == self._key_release_event_type():
			key_event = event
			if key_event.isAutoRepeat():
				return False

			if key_event.key() == self._space_key():
				self.stop_burst_capture()
				return True

		return super().eventFilter(watched, event)

	def closeEvent(self, event) -> None:
		app = QApplication.instance()
		if app is not None:
			app.removeEventFilter(self)
		if hasattr(self, "frame_timer"):
			self.frame_timer.stop()
		if self.capture is not None and self.capture.isOpened():
			self.capture.release()
		event.accept()


def main() -> None:
	app = QApplication(sys.argv)
	window = CameraWindow()
	window.show()
	window.activateWindow()
	window.raise_()
	if PYQT_VERSION == 6:
		sys.exit(app.exec())
	else:
		sys.exit(app.exec_())


if __name__ == "__main__":
	main()
