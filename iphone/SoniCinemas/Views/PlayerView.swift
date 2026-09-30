import SwiftUI
import AVKit

struct PlayerView: UIViewControllerRepresentable {
    let videoURL: URL
    
    func makeUIViewController(context: Context) -> AVPlayerViewController {
        let player = AVPlayer(url: videoURL)
        let controller = AVPlayerViewController()
        controller.player = player
        controller.showsPlaybackControls = true
        controller.allowsPictureInPicturePlayback = true
        player.play()
        return controller
    }
    
    func updateUIViewController(_ uiViewController: AVPlayerViewController, context: Context) {}
}

struct PlayerScreen: View {
    let title: String
    let videoUrlString: String
    @Environment(\.presentationMode) var presentationMode
    
    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            
            if let url = URL(string: videoUrlString) {
                PlayerView(videoURL: url)
                    .edgesIgnoringSafeArea(.all)
            } else {
                VStack(spacing: 15) {
                    Image(systemName: "exclamationmark.triangle.fill")
                        .font(.system(size: 50))
                        .foregroundColor(.yellow)
                    Text("Invalid Video URL")
                        .foregroundColor(.white)
                        .font(.headline)
                    Button("Close") {
                        presentationMode.wrappedValue.dismiss()
                    }
                    .padding()
                    .background(Color.red)
                    .foregroundColor(.white)
                    .cornerRadius(8)
                }
            }
        }
        .navigationBarHidden(true)
    }
}
