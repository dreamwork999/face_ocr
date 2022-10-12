import face_recognition
import numpy as np
from PIL import Image, ImageDraw

# This is an example of running face recognition on a single image
# and drawing a box around each person that was identified.

# Load a sample picture and learn how to recognize it.
caleb_image = face_recognition.load_image_file("caleb.jpg")
caleb_face_encoding = face_recognition.face_encodings(caleb_image)[0]

# Load a second sample picture and learn how to recognize it.
john_image = face_recognition.load_image_file("john.jpg")
john_face_encoding = face_recognition.face_encodings(john_image)[0]

mike_image = face_recognition.load_image_file("mike.jpg")
mike_face_encoding = face_recognition.face_encodings(mike_image)[0]

# Create arrays of known face encodings and their names
known_face_encodings = [
    caleb_face_encoding,
    john_face_encoding,
    mike_face_encoding
]
known_face_names = [
    "Caleb",
    "John",
    "mike"
]
print('Learned encoding for', len(known_face_encodings), 'images.')


# Load an image with an unknown face
unknown_image = face_recognition.load_image_file("obama.jpg")

# Find all the faces and face encodings in the unknown image
face_locations = face_recognition.face_locations(unknown_image)
face_encodings = face_recognition.face_encodings(unknown_image, face_locations)

# Loop through each face found in the unknown image
for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
    # See if the face is a match for the known face(s)
    matches = face_recognition.compare_faces(known_face_encodings, face_encoding)

    name = "Unknown"

    # Or instead, use the known face with the smallest distance to the new face
    face_distances = face_recognition.face_distance(known_face_encodings, face_encoding)
    best_match_index = np.argmin(face_distances)
    if matches[best_match_index]:
        name = known_face_names[best_match_index]
    print ("found face for: ", name, "with distance: ", face_distances)
