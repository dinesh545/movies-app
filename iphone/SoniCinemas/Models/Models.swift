import Foundation

struct AuthResponse: Codable {
    let success: Bool?
    let status: String?
    let message: String?
    let sessionToken: String?
    let user: UserInfo?
    
    enum CodingKeys: String, CodingKey {
        case success, status, message
        case sessionToken = "session_token"
        case user
    }
}

struct UserInfo: Codable {
    let id: Int?
    let name: String?
    let email: String?
    let mobile: String?
}

struct MediaResponse: Codable {
    let status: String?
    let message: String?
    let movies: [MediaItem]?
    let series: [MediaItem]?
    let data: [MediaItem]?
}

struct MediaItem: Codable, Identifiable {
    let id: Int
    let title: String
    let description: String?
    let posterUrl: String?
    let bannerUrl: String?
    let videoUrl: String?
    let category: String?
    let year: String?
    let rating: String?
    let duration: String?
    let genre: String?
    
    enum CodingKeys: String, CodingKey {
        case id, title, description, category, year, rating, duration, genre
        case posterUrl = "poster_url"
        case bannerUrl = "banner_url"
        case videoUrl = "video_url"
    }
}

struct SeriesDetailResponse: Codable {
    let status: String?
    let message: String?
    let series: MediaItem?
    let seasons: [Season]?
}

struct Season: Codable, Identifiable {
    var id: Int { seasonNumber }
    let seasonNumber: Int
    let seasonName: String?
    let episodes: [Episode]?
    
    enum CodingKeys: String, CodingKey {
        case seasonNumber = "season_number"
        case seasonName = "season_name"
        case episodes
    }
}

struct Episode: Codable, Identifiable {
    let id: Int
    let episodeNumber: Int?
    let title: String?
    let description: String?
    let thumbnail: String?
    let videoUrl: String?
    let duration: String?
    
    enum CodingKeys: String, CodingKey {
        case id, title, description, thumbnail, duration
        case episodeNumber = "episode_number"
        case videoUrl = "video_url"
    }
}

struct LocalDownloadItem: Identifiable, Codable {
    let id: String
    let mediaId: Int
    let title: String
    let posterUrl: String?
    let localFilePath: String
    let downloadedAt: Date
}
