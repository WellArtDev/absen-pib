import { CameraType } from 'expo-camera';
import * as FileSystem from 'expo-file-system';

/**
 * Compress photo by reading as base64 and reducing quality client-side.
 * Sends to API which does server-side resize via GD library.
 */
export async function compressPhoto(uri: string): Promise<string> {
  // Read as base64 and let server handle compression
  const base64 = await FileSystem.readAsStringAsync(uri, {
    encoding: FileSystem.EncodingType.Base64,
  });
  return `data:image/jpeg;base64,${base64}`;
}

export async function takeSelfie(): Promise<string> {
  throw new Error('Gunakan CameraView component untuk mengambil foto');
}

export const CAMERA_FACING: CameraType = 'front';

export const CAMERA_OPTIONS = {
  quality: 0.7,
  maxWidth: 1080,
  skipProcessing: false,
};
