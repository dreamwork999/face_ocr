import face_recognition
import sys
import numpy as np
import json
from PIL import Image, ImageDraw

try:
    # get file from command line parameter
    input_image = face_recognition.load_image_file(sys.argv[1])
    # input_image = face_recognition.load_image_file("/var/www/html/ocr/input.jpg")
    input_face_encoding = face_recognition.face_encodings(input_image)[0]
    data = json.dumps(input_face_encoding.tolist())
    print('{"status":"success", "message": "Learned face encoding OK", "data":' + data + '}')
except:
    print('{"status":"error", "message": "Face learning failed. Is there a face in this image??"}')
