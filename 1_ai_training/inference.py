from __future__ import annotations

import importlib
import sys
from pathlib import Path

import cv2
from ultralytics import YOLO

def _load_qt_modules():
	candidates = (
		("PyQt5.QtCore", "PyQt5.QtGui", "PyQt5.QtWidgets"),
		("PySide6.QtCore", "PySide6.QtGui", "PySide6.QtWidgets"),
	)

	last_error: ImportError | None = None
	for core_name, gui_name, widgets_name in candidates:
		try:
			core = importlib.import_module(core_name)
			gui = importlib.import_module(gui_name)
			widgets = importlib.import_module(widgets_name)
			return (
				core.QTimer,
				core.Qt,
				gui.QImage,
				gui.QPixmap,
				widgets.QApplication,
				widgets.QDialog,
				widgets.QFrame,
				widgets.QComboBox,
				widgets.QHBoxLayout,
				widgets.QGridLayout,
				widgets.QLabel,
				widgets.QSizePolicy,
				widgets.QVBoxLayout,
				widgets.QWidget,
			)
		except ImportError as error:  # pragma: no cover - environment dependent
			last_error = error

	raise ImportError("PyQt5 atau PySide6 tidak ditemukan") from last_error


QTimer, Qt, QImage, QPixmap, QApplication, QDialog, QFrame, QComboBox, QHBoxLayout, QGridLayout, QLabel, QSizePolicy, QVBoxLayout, QWidget = _load_qt_modules()


BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "model.pt"
IMAGE_DIR = BASE_DIR / "inferences"
IMAGE_PATHS = [IMAGE_DIR / "random_1.jpg", IMAGE_DIR / "random_2.jpg", IMAGE_DIR / "random_3.jpg"]


def scan_available_cameras(max_index: int = 10) -> list[int]:
	available = []
	for index in range(max_index + 1):
		capture = cv2.VideoCapture(index)
		try:
			if capture.isOpened():
				available.append(index)
		finally:
			capture.release()
	return available or [0]


class ImagePreviewLabel(QLabel):
	def __init__(self, text: str = "") -> None:
		super().__init__(text)
		self._source_pixmap = None

	def set_preview_pixmap(self, pixmap) -> None:
		self._source_pixmap = pixmap
		self._update_scaled_pixmap()

	def resizeEvent(self, event) -> None:
		super().resizeEvent(event)
		self._update_scaled_pixmap()

	def _update_scaled_pixmap(self) -> None:
		if self._source_pixmap is None:
			return
		scaled = self._source_pixmap.scaled(self.contentsRect().size(), Qt.KeepAspectRatio, Qt.SmoothTransformation)
		super().setPixmap(scaled)


def bgr_to_pixmap(image_bgr):
	rgb = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2RGB)
	height, width, channels = rgb.shape
	bytes_per_line = channels * width
	q_image = QImage(rgb.data, width, height, bytes_per_line, QImage.Format_RGB888)
	return QPixmap.fromImage(q_image.copy())


def infer_image(model: YOLO, image_path: Path):
	frame = cv2.imread(str(image_path))
	if frame is None:
		raise FileNotFoundError(f"Gagal membaca gambar: {image_path}")

	result = model.predict(source=frame, verbose=False)[0]
	return bgr_to_pixmap(result.plot())


def infer_camera_frame(model: YOLO, capture):
	success, frame = capture.read()
	if not success:
		raise RuntimeError("Gagal mengambil frame dari kamera index 0")

	result = model.predict(source=frame, verbose=False, conf=0.6)[0]
	return bgr_to_pixmap(result.plot())


def create_tile(title: str, header_widget=None):
	frame = QFrame()
	frame.setObjectName("tileFrame")
	frame.setStyleSheet(
		"""
		QFrame#tileFrame {
			background: #111827;
			border: 1px solid #334155;
			border-radius: 16px;
		}
		QLabel {
			color: #e5e7eb;
		}
		"""
	)

	layout = QVBoxLayout(frame)
	layout.setContentsMargins(10, 10, 10, 10)
	layout.setSpacing(8)

	if header_widget is None:
		caption = QLabel(title)
		caption.setAlignment(Qt.AlignCenter)
		caption.setStyleSheet("font-size: 14px; font-weight: 600; padding: 2px 4px;")
		layout.addWidget(caption)
	else:
		layout.addWidget(header_widget)

	image_label = QLabel("Memuat...")
	image_label = ImagePreviewLabel("Memuat...")
	image_label.setAlignment(Qt.AlignCenter)
	image_label.setStyleSheet(
		"""
		QLabel {
			background: #0f172a;
			border: 1px dashed #475569;
			border-radius: 12px;
			color: #94a3b8;
		}
		"""
	)
	image_label.setSizePolicy(QSizePolicy.Expanding, QSizePolicy.Expanding)

	layout.addWidget(image_label, 1)
	return frame, image_label


class InferenceDialog(QDialog):
	def __init__(self, model: YOLO) -> None:
		super().__init__()
		self.setWindowTitle("YOLO Inference")
		self.setModal(True)
		self.setFixedSize(1200, 900)
		self.setStyleSheet("QDialog { background: #0f172a; }")

		self._model = model

		root = QVBoxLayout(self)
		root.setContentsMargins(16, 16, 16, 16)
		root.setSpacing(12)

		heading = QLabel("Inference Preview")
		heading.setAlignment(Qt.AlignCenter)
		heading.setStyleSheet("color: #f8fafc; font-size: 22px; font-weight: 700; padding: 4px 0;")
		root.addWidget(heading)

		grid_host = QWidget()
		grid = QGridLayout(grid_host)
		grid.setContentsMargins(0, 0, 0, 0)
		grid.setHorizontalSpacing(12)
		grid.setVerticalSpacing(12)

		self._available_cameras = scan_available_cameras()
		self._camera_index = self._available_cameras[0]

		self._tiles = []
		titles = ["Inference 1", "Inference 2", "Inference 3", "Realtime Kamera"]
		for index, title in enumerate(titles):
			if index == 3:
				header = QWidget()
				header_layout = QHBoxLayout(header)
				header_layout.setContentsMargins(2, 2, 2, 2)
				header_layout.setSpacing(10)

				camera_label = QLabel(title)
				camera_label.setStyleSheet("color: #f8fafc; font-size: 14px; font-weight: 600;")

				self._camera_selector = QComboBox()
				for camera_index in self._available_cameras:
					self._camera_selector.addItem(f"Kamera {camera_index}", camera_index)
				self._camera_selector.setCurrentIndex(self._camera_selector.findData(self._camera_index))
				self._camera_selector.currentIndexChanged.connect(self._change_camera)

				header_layout.addWidget(camera_label)
				header_layout.addWidget(self._camera_selector)
				header_layout.addStretch(1)
				frame, image_label = create_tile(title, header)
			else:
				frame, image_label = create_tile(title)
			self._tiles.append(image_label)
			row = 0 if index < 2 else 1
			col = 0 if index % 2 == 0 else 1
			grid.addWidget(frame, row, col)

		root.addWidget(grid_host, 1)

		self._still_pixmaps = [infer_image(model, path) for path in IMAGE_PATHS]
		self._refresh_stills()

		self._capture = cv2.VideoCapture(self._camera_index)
		if not self._capture.isOpened():
			raise RuntimeError(f"Kamera index {self._camera_index} tidak bisa dibuka")

		self._camera_timer = QTimer(self)
		self._camera_timer.setInterval(120)
		self._camera_timer.timeout.connect(self._refresh_camera)
		self._camera_timer.start()

	def _refresh_stills(self) -> None:
		for index, pixmap in enumerate(self._still_pixmaps):
			self._set_tile_pixmap(index, pixmap)

	def _refresh_camera(self) -> None:
		try:
			pixmap = infer_camera_frame(self._model, self._capture)
		except RuntimeError:
			return
		self._set_tile_pixmap(3, pixmap)

	def _change_camera(self) -> None:
		selected_index = self._camera_selector.currentData()
		if selected_index is None:
			return
		if selected_index == self._camera_index:
			return

		if self._capture.isOpened():
			self._capture.release()

		self._camera_index = int(selected_index)
		self._capture = cv2.VideoCapture(self._camera_index)
		if not self._capture.isOpened():
			raise RuntimeError(f"Kamera index {self._camera_index} tidak bisa dibuka")

	def _set_tile_pixmap(self, index: int, pixmap) -> None:
		tile = self._tiles[index]
		tile.set_preview_pixmap(pixmap)
		tile.setText("")

	def closeEvent(self, event) -> None:
		if self._camera_timer.isActive():
			self._camera_timer.stop()
		if self._capture.isOpened():
			self._capture.release()
		super().closeEvent(event)


def main() -> int:
	if not MODEL_PATH.exists():
		raise FileNotFoundError(f"Model tidak ditemukan: {MODEL_PATH}")

	missing_images = [str(path) for path in IMAGE_PATHS if not path.exists()]
	if missing_images:
		raise FileNotFoundError("Gambar berikut tidak ditemukan: " + ", ".join(missing_images))

	app = QApplication(sys.argv)
	model = YOLO(str(MODEL_PATH))
	dialog = InferenceDialog(model)
	return dialog.exec()


if __name__ == "__main__":
	raise SystemExit(main())
