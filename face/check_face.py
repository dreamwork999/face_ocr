import face_recognition
import numpy as np
from PIL import Image, ImageDraw
import sys
import json

known_face_encoding = json.loads(sys.argv[1])

# Create arrays of known face encodings and their names
known_face_encodings = [
    known_face_encoding
]
known_face_names = [
    "true"
]

# Load an image with an unknown face
unknown_image = face_recognition.load_image_file("/var/www/html/face/input.jpg")

# Find all the faces and face encodings in the unknown image
face_locations = face_recognition.face_locations(unknown_image)
face_encodings = face_recognition.face_encodings(unknown_image, face_locations)

# Loop through each face found in the unknown image
for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
    # See if the face is a match for the known face(s)
    matches = face_recognition.compare_faces(known_face_encodings, face_encoding)

    name = "false"

    # Or instead, use the known face with the smallest distance to the new face
    face_distances = face_recognition.face_distance(known_face_encodings, face_encoding)
    best_match_index = np.argmin(face_distances)
    if matches[best_match_index]:
        name = known_face_names[best_match_index]
    # print ("found face for: ", name, "with distance: ", face_distances)
    print('{"status":"success", "message":"face check completed", "data":{"matched":', name,', "distance":', face_distances, '}}')
