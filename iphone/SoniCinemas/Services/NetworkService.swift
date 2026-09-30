import Foundation

class NetworkService {
    static let shared = NetworkService()
    
    // Change this to match your server domain or local IP when testing locally
    var baseURL = "https://your-domain.com/"
    
    private init() {}
    
    func setBaseURL(_ url: String) {
        var formatted = url
        if !formatted.hasSuffix("/") {
            formatted += "/"
        }
        self.baseURL = formatted
    }
    
    func fetchMovies(completion: @escaping (Result<[MediaItem], Error>) -> Void) {
        guard let url = URL(string: baseURL + "api/movies.php") else { return }
        
        URLSession.shared.dataTask(with: url) { data, _, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            guard let data = data else { return }
            do {
                let response = try JSONDecoder().decode(MediaResponse.self, from: data)
                let items = response.movies ?? response.data ?? []
                completion(.success(items))
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
    
    func fetchSeriesList(completion: @escaping (Result<[MediaItem], Error>) -> Void) {
        guard let url = URL(string: baseURL + "api/series.php") else { return }
        
        URLSession.shared.dataTask(with: url) { data, _, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            guard let data = data else { return }
            do {
                let response = try JSONDecoder().decode(MediaResponse.self, from: data)
                let items = response.series ?? response.data ?? []
                completion(.success(items))
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
    
    func fetchSeriesDetail(seriesId: Int, completion: @escaping (Result<SeriesDetailResponse, Error>) -> Void) {
        guard let url = URL(string: baseURL + "api/series.php?id=\(seriesId)") else { return }
        
        URLSession.shared.dataTask(with: url) { data, _, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            guard let data = data else { return }
            do {
                let response = try JSONDecoder().decode(SeriesDetailResponse.self, from: data)
                completion(.success(response))
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
    
    func postForm(endpoint: String, params: [String: String], completion: @escaping (Result<AuthResponse, Error>) -> Void) {
        guard let url = URL(string: baseURL + endpoint) else { return }
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/x-www-form-urlencoded", forHTTPHeaderField: "Content-Type")
        
        let bodyString = params.map { "\($0.key)=\($0.value.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? "")" }.joined(separator: "&")
        request.httpBody = bodyString.data(using: .utf8)
        
        URLSession.shared.dataTask(with: request) { data, _, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            guard let data = data else { return }
            do {
                let response = try JSONDecoder().decode(AuthResponse.self, from: data)
                completion(.success(response))
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
}
