import axios from 'axios';

const NOMINATIM_URL = 'https://nominatim.openstreetmap.org';

interface NominatimResponse {
  display_name: string;
}

export async function reverseGeocode(
  latitude: number,
  longitude: number
): Promise<string | null> {
  try {
    const response = await axios.get<NominatimResponse>(
      `${NOMINATIM_URL}/reverse`,
      {
        params: {
          lat: latitude,
          lon: longitude,
          format: 'json',
          'accept-language': 'id',
        },
        headers: {
          'User-Agent': 'AbsenPIB/1.0',
        },
      }
    );
    return response.data?.display_name || null;
  } catch {
    return null;
  }
}
